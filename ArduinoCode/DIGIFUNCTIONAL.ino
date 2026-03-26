/*
  Lilygo T-Display-S3-Long (AXS15231B) - DigiPrices UI + WiFi/HTTP update
  Uses nikthefix "sprite only mode" + lcd_PushColors_rotated_90()

  - Backlight stays OFF until first valid update arrives
  - Redraw only when product data changes (hash compare)
  - Proper EAN-13 barcode rendering (with checksum fix if needed)
  - Price uses dot separator (e.g., 8.99) and prints "EUR" next to it

  Required libraries:
    - TFT_eSPI
    - ESP32 core WiFi + HTTPClient
    - ArduinoJson (recommended)
  Required driver files:
    - AXS15231B.h/.cpp providing:
        axs15231_init()
        lcd_fill()
        lcd_PushColors_rotated_90(x,y,w,h,uint16_t*)
        TFT_BL pin define, etc.
*/

#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <TFT_eSPI.h>
#include "AXS15231B.h"
#include <WebServer.h>

// -------- ArduinoJson (recommended) --------

// ===================== Web Initilaization ==================

WebServer server(80);

// ===================== USER SETTINGS =====================
const char* SSID = "HT_398542_EXT2.4G";
const char* PASS = "45724305015294986009";
//const char* URL_PRODUCT   = "http://192.168.1.69/api/product.php";

// Poll interval (ms): how often we check server for updates
//static const uint32_t POLL_MS = 2500;

// HTTP timeout (ms)
static const uint32_t HTTP_TIMEOUT_MS = 2500;

// ===================== SCREEN / SPRITE =====================
static const int W = 640;
static const int H = 180;

TFT_eSPI tft = TFT_eSPI();
TFT_eSprite sprite = TFT_eSprite(&tft);

// ===================== JSON KEYS (adjust to your PHP output) =====================
static const char* JSON_ID          = "id";
static const char* JSON_NAME        = "name";
static const char* JSON_PRICE       = "price";
static const char* JSON_PRICE_KG    = "price_per_kg";
static const char* JSON_BARCODE     = "barcode";
static const char* JSON_UPDATED     = "updated";

// ===================== Data model =====================
struct ProductData {
  String id;
  String name;
  String price;        // "8.99"
  String pricePerKg;   // "2.49"
  String barcode;      // 13 digits
  String updated;      // timestamp string
};

static ProductData g_current;
static uint32_t g_lastHash = 0;
static bool g_hasDrawnOnce = false;

// ===================== Utility: stable hash for change detection =====================
static uint32_t fnv1a_32(const uint8_t* data, size_t len) {
  uint32_t h = 2166136261u;
  for (size_t i = 0; i < len; i++) { h ^= data[i]; h *= 16777619u; }
  return h;
}

static uint32_t hashProduct(const ProductData& p) {
  // Hash only the fields that should trigger redraw
  String joined = p.id + "|" + p.name + "|" + p.price + "|" + p.pricePerKg + "|" + p.barcode + "|" + p.updated;
  return fnv1a_32((const uint8_t*)joined.c_str(), joined.length());
}

// ===================== WiFi helpers =====================
static bool wifiEnsureConnected(uint32_t maxWaitMs) {
  if (WiFi.status() == WL_CONNECTED) return true;

  WiFi.mode(WIFI_STA);
  WiFi.begin(SSID, PASS);

  uint32_t t0 = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - t0) < maxWaitMs) {
    delay(120);
  }
  return (WiFi.status() == WL_CONNECTED);
}

// ===================== HTTP GET =====================
/*static bool httpGetString(const char* url, String& out) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);
  http.begin(url);

  int code = http.GET();
  if (code <= 0) {
    http.end();
    return false;
  }

  out = http.getString();
  http.end();
  return true;
}*/

// ===================== JSON parse =====================
static bool parseProductJson(const String& payload, ProductData& out) {
  // Size note: if your JSON grows, increase doc size.
  StaticJsonDocument<512> doc;

  DeserializationError err = deserializeJson(doc, payload);
  if (err) return false;

  // If your PHP returns an array, adapt here. This assumes a single object.
  out.id        = doc[JSON_ID]       | "";
  out.name      = doc[JSON_NAME]     | "";
  out.price     = doc[JSON_PRICE]    | "";
  out.pricePerKg= doc[JSON_PRICE_KG] | "";
  out.barcode   = doc[JSON_BARCODE]  | "";
  out.updated   = doc[JSON_UPDATED]  | "";

  // Basic sanity
  if (out.name.length() == 0) return false;
  if (out.price.length() == 0) return false;

  return true;
}

// ===================== EAN-13 barcode rendering =====================
// Encoding tables (EAN-13):
// Left side digits (positions 2..7) use L or G encoding based on first digit parity pattern.
// Right side digits (positions 8..13) always use R encoding.

static const char* L_CODE[10] = {
  "0001101","0011001","0010011","0111101","0100011",
  "0110001","0101111","0111011","0110111","0001011"
};

static const char* G_CODE[10] = {
  "0100111","0110011","0011011","0100001","0011101",
  "0111001","0000101","0010001","0001001","0010111"
};

static const char* R_CODE[10] = {
  "1110010","1100110","1101100","1000010","1011100",
  "1001110","1010000","1000100","1001000","1110100"
};

// Parity patterns for first digit (digit0). For digits 2..7:
// 'L' or 'G' pattern.
static const char* PARITY[10] = {
  "LLLLLL",
  "LLGLGG",
  "LLGGLG",
  "LLGGGL",
  "LGLLGG",
  "LGGLLG",
  "LGGGLL",
  "LGLGLG",
  "LGLGGL",
  "LGGLGL"
};

static bool isDigits(const String& s) {
  for (int i = 0; i < (int)s.length(); i++) {
    if (s[i] < '0' || s[i] > '9') return false;
  }
  return true;
}

static int ean13ChecksumDigit(const String& first12) {
  // first12 must be 12 digits
  int sum = 0;
  for (int i = 0; i < 12; i++) {
    int d = first12[i] - '0';
    // positions are 1..12; even positions weight 3 in EAN-13
    // i=0 => pos1 => weight1
    int pos = i + 1;
    sum += (pos % 2 == 0) ? (3 * d) : d;
  }
  int mod = sum % 10;
  return (mod == 0) ? 0 : (10 - mod);
}

static String normalizeEan13(String digits) {
  digits.trim();

  // Accept 12 digits => compute checksum and append
  if (digits.length() == 12 && isDigits(digits)) {
    int cd = ean13ChecksumDigit(digits);
    digits += char('0' + cd);
    return digits;
  }

  // Accept 13 digits => optionally fix checksum if wrong
  if (digits.length() == 13 && isDigits(digits)) {
    String first12 = digits.substring(0, 12);
    int cd = ean13ChecksumDigit(first12);
    if ((digits[12] - '0') != cd) {
      digits[12] = char('0' + cd);
    }
    return digits;
  }

  // Anything else: return empty => draw placeholder
  return "";
}

static void drawEan13Barcode(int x, int y, int w, int h, const String& rawDigits) {
  // White label background
  sprite.fillRect(x, y, w, h + 22, TFT_WHITE);

  String digits = normalizeEan13(rawDigits);
  if (digits.length() != 13) {
    sprite.setTextColor(TFT_BLACK, TFT_WHITE);
    sprite.drawString("INVALID BARCODE", x + 10, y + 10, 2);
    return;
  }

  // Build the full bit pattern:
  // Start guard: 101
  // Left 6 digits: 42 bits
  // Center guard: 01010
  // Right 6 digits: 42 bits
  // End guard: 101
  // Total modules: 3 + 42 + 5 + 42 + 3 = 95

  String bits;
  bits.reserve(95);

  bits += "101";

  int first = digits[0] - '0';
  const char* parity = PARITY[first];

  // Left side digits 1..6 => digits[1]..digits[6]
  for (int i = 0; i < 6; i++) {
    int d = digits[1 + i] - '0';
    if (parity[i] == 'L') bits += L_CODE[d];
    else                  bits += G_CODE[d];
  }

  bits += "01010";

  // Right side digits 7..12 => digits[7]..digits[12]
  for (int i = 0; i < 6; i++) {
    int d = digits[7 + i] - '0';
    bits += R_CODE[d];
  }

  bits += "101";

  // Render bars
  const int modules = 95;
  // Fit modules into available width
  int moduleW = w / modules;
  if (moduleW < 1) moduleW = 1;

  int barWTotal = moduleW * modules;
  int x0 = x + (w - barWTotal) / 2;

  // Bar heights: guards slightly taller
  int barH = h;
  int guardH = h + 6;

  // Draw bars (black on white)
  for (int i = 0; i < modules; i++) {
    bool isBar = (bits[i] == '1');
    if (!isBar) continue;

    bool isGuard =
      (i < 3) ||                        // start guard
      (i >= 45 && i < 50) ||            // center guard
      (i >= 92);                        // end guard

    int hh = isGuard ? guardH : barH;
    sprite.fillRect(x0 + i * moduleW, y, moduleW, hh, TFT_BLACK);
  }

  // Digits below barcode
  sprite.setTextColor(TFT_BLACK, TFT_WHITE);

  // EAN-13 standard layout: first digit left, then 6 digits, then 6 digits
  // We’ll print as full string centered (simple + readable)
  sprite.drawString(digits, x + 10, y + h + 6, 2);
}

// ===================== UI drawing =====================
static void drawPriceTag(const ProductData& p) {
  sprite.fillSprite(TFT_BLACK);

  // --- Header row ---
  sprite.setTextColor(TFT_WHITE, TFT_BLACK);
  sprite.drawString(p.name, 20, 12, 4);

  // ID aligned right on same baseline-ish
  String idText = "ID: " + p.id;
  int idW = sprite.textWidth(idText, 4);
  sprite.drawString(idText, W - 20 - idW, 12, 4);

  sprite.drawFastHLine(20, 46, W - 40, TFT_DARKGREY);

  // --- Big price (no font 7) ---
  // Use font 6 for larger digits; if it looks too big/small, switch to 4.
  // Ensure dot is visible in this font.
  sprite.setTextColor(TFT_WHITE, TFT_BLACK);

  // Price text (e.g., "8.99")
  int px = 20;
  int py = 58;
  sprite.drawString(p.price, px, py, 6);

  // "EUR" placed immediately after price based on rendered width
  int priceW = sprite.textWidth(p.price, 6);
  sprite.setTextColor(TFT_LIGHTGREY, TFT_BLACK);
  sprite.drawString("EUR", px + priceW + 12, py + 18, 4);

  // --- Cijena/kg block moved right ---
  sprite.setTextColor(TFT_LIGHTGREY, TFT_BLACK);
  sprite.drawString("Cijena/kg:", 460, 58, 4);
  sprite.setTextColor(TFT_WHITE, TFT_BLACK);

  String perKg = p.pricePerKg + " EUR/kg";
  sprite.drawString(perKg, 460, 90, 4);

  // --- Barcode area ---
  // Tuned box; adjust if you want more/less room
  const int bx = 20, by = 122, bw = 420, bh = 38;
  drawEan13Barcode(bx, by, bw, bh, p.barcode);

  // --- Last update ---
  sprite.setTextColor(TFT_LIGHTGREY, TFT_BLACK);
  sprite.drawString("Zadnje azuriranje:", 460, 126, 2);
  sprite.setTextColor(TFT_WHITE, TFT_BLACK);
  sprite.drawString(p.updated, 460, 146, 2);

  // Push to LCD in landscape (rotated 90)
  lcd_PushColors_rotated_90(0, 0, W, H, (uint16_t*)sprite.getPointer());
}

// ===================== Boot / panel =====================
static void panelBlankAndBacklightOff() {
  pinMode(TFT_BL, OUTPUT);
  digitalWrite(TFT_BL, LOW);      // OFF
  // Clear panel (portrait coords in driver)
  lcd_fill(0, 0, 180, 640, 0x0000);
}

static void backlightOn() {
  digitalWrite(TFT_BL, HIGH);
}

void handleUpdate() {
  String body = server.arg("plain");
  ProductData incoming;
  
  if (!parseProductJson(body, incoming)) {
    server.send(400, "application/json", "{\"success\":false}");
    return;
  }

  uint32_t h = hashProduct(incoming);
  if (h == g_lastHash) {
    server.send(200, "application/json", "{\"success\":true}");
    return;
  }
  
  g_lastHash = h;
  g_current = incoming;

  if (!g_hasDrawnOnce) {
    backlightOn();
    g_hasDrawnOnce = true;
  }

  drawPriceTag(g_current);
  server.send(200, "application/json", "{\"success\":true}");
}

void drawIpScreen(){
  backlightOn();
  sprite.fillSprite(TFT_BLACK);
  sprite.drawString("DigiPrices connect!", 10, 80, 4);
  sprite.drawString("IP:" + WiFi.localIP().toString(), 10, 120, 4);
  lcd_PushColors_rotated_90(0, 0, W, H, (uint16_t*)sprite.getPointer());
}

// ===================== Main =====================
void setup() {
  delay(1000);
  Serial.begin(115200);
  delay(150);

  // Keep display dark until first real update
  axs15231_init();
  panelBlankAndBacklightOff();


  // Sprite in PSRAM
  sprite.createSprite(W, H);
  sprite.setSwapBytes(1);

  // Optional: connect WiFi immediately (but we still don't draw anything yet)
  wifiEnsureConnected(8000);
  Serial.print("[WiFi] Connected! IP: ");
  Serial.println(WiFi.localIP());
  drawIpScreen();

  server.on("/update", HTTP_POST, handleUpdate);
  server.begin();
  Serial.println("[BOOT] Ready (waiting for updates)");
}

static uint32_t lastPoll = 0;

void loop() {
  server.handleClient();
  delay(5);
}


