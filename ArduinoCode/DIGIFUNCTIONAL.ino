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
  Required driver files:x
    - AXS15231B.h/.cpp providing:
        axs15231_init()
        lcd_fill()
        lcd_PushColors_rotated_90(x,y,w,h,uint16_t*)
        TFT_BL pin define, etc.
*/

#include <Arduino.h>
#include <WiFi.h>
#include <WiFiClient.h>
#include <WiFiAP.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <TFT_eSPI.h>
#include "AXS15231B.h"
#include <WebServer.h>
#include <Preferences.h>

Preferences prefs;
Preferences prod;
// -------- ArduinoJson (recommended) --------

// ===================== Web Initilaization ==================

WebServer server(80);

// ===================== USER SETTINGS =====================
// const char* SSID = "HT_398542_EXT2.4G";
// const char* PASS = "45724305015294986009";
// const char* URL_PRODUCT   = "http://192.168.1.69/api/product.php";

// Poll interval (ms): how often we check server for updates
// static const uint32_t POLL_MS = 2500;

// HTTP timeout (ms)
static uint32_t buttonPressStart = 0;
static const uint32_t HTTP_TIMEOUT_MS = 2500;

// ===================== SCREEN / SPRITE =====================
static const int W = 640;
static const int H = 180;

TFT_eSPI tft = TFT_eSPI();
TFT_eSprite sprite = TFT_eSprite(&tft);

// ===================== JSON KEYS (adjust to your PHP output) =====================
static const char *JSON_ID_DISPLAY = "id_display";
static const char *JSON_ID = "id";
static const char *JSON_NAME = "name";
static const char *JSON_PRICE = "price";
static const char *JSON_PRICE_KG = "price_per_kg";
static const char *JSON_BARCODE = "barcode";
static const char *JSON_UPDATED = "updated";
static const char *JSON_DISCOUNT_PER = "discount_per";
static const char *JSON_DISCOUNT_PRICE = "discount_price";
static const char *JSON_LOWEST_PRICE = "lowest_price";
static const char *JSON_DISCOUNT_END = "discount_end";
static const char *JSON_UNIT = "unit";

// ===================== Data model =====================
struct ProductData
{
  String id_display;
  String id;
  String name;
  String price;      // "8.99"
  String pricePerKg; // "2.49"
  String barcode;    // 13 digits
  String updated;    // timestamp string
  String discount_per;
  String discount_price;
  String lowest_price;
  String discount_end;
  String unit;
};

static ProductData g_current;
static uint32_t g_lastHash = 0;
static bool g_hasDrawnOnce = false;

// ===================== Utility: stable hash for change detection =====================
static uint32_t fnv1a_32(const uint8_t *data, size_t len)
{
  uint32_t h = 2166136261u;
  for (size_t i = 0; i < len; i++)
  {
    h ^= data[i];
    h *= 16777619u;
  }
  return h;
}

static uint32_t hashProduct(const ProductData &p)
{
  String joined = p.id + "|" + p.name + "|" + p.price + "|" + p.pricePerKg + "|" + p.barcode + "|" + p.updated
                + "|" + p.discount_per + "|" + p.discount_price + "|" + p.discount_end;
  return fnv1a_32((const uint8_t *)joined.c_str(), joined.length());
}

// ===================== WiFi helpers =====================
static bool wifiEnsureConnected(String ssid, String pass, uint32_t maxWaitMs)
{
  if (WiFi.status() == WL_CONNECTED)
    return true;

  WiFi.begin(ssid.c_str(), pass.c_str());

  uint32_t t0 = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - t0) < maxWaitMs)
  {
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
static bool parseProductJson(const String &payload, ProductData &out)
{
  // Size note: if your JSON grows, increase doc size.
  StaticJsonDocument<512> doc;

  DeserializationError err = deserializeJson(doc, payload);
  if (err)
    return false;

  // If your PHP returns an array, adapt here. This assumes a single object.
  out.id_display = doc[JSON_ID_DISPLAY] | "";
  out.id = doc[JSON_ID] | "";
  out.name = doc[JSON_NAME] | "";
  out.price = doc[JSON_PRICE] | "";
  out.pricePerKg = doc[JSON_PRICE_KG] | "";
  out.barcode = doc[JSON_BARCODE] | "";
  out.updated = doc[JSON_UPDATED] | "";
  out.discount_per = doc[JSON_DISCOUNT_PER] | "";
  out.discount_price = doc[JSON_DISCOUNT_PRICE] | "";
  out.lowest_price = doc[JSON_LOWEST_PRICE] | "";
  out.discount_end = doc[JSON_DISCOUNT_END] | "";
  out.unit = doc[JSON_UNIT] | "";

  // Basic sanity
  if (out.name.length() == 0)
    return false;
  if (out.price.length() == 0)
    return false;

  return true;
}

// ===================== EAN-13 barcode rendering =====================
// Encoding tables (EAN-13):
// Left side digits (positions 2..7) use L or G encoding based on first digit parity pattern.
// Right side digits (positions 8..13) always use R encoding.

static const char *L_CODE[10] = {
    "0001101", "0011001", "0010011", "0111101", "0100011",
    "0110001", "0101111", "0111011", "0110111", "0001011"};

static const char *G_CODE[10] = {
    "0100111", "0110011", "0011011", "0100001", "0011101",
    "0111001", "0000101", "0010001", "0001001", "0010111"};

static const char *R_CODE[10] = {
    "1110010", "1100110", "1101100", "1000010", "1011100",
    "1001110", "1010000", "1000100", "1001000", "1110100"};

// Parity patterns for first digit (digit0). For digits 2..7:
// 'L' or 'G' pattern.
static const char *PARITY[10] = {
    "LLLLLL",
    "LLGLGG",
    "LLGGLG",
    "LLGGGL",
    "LGLLGG",
    "LGGLLG",
    "LGGGLL",
    "LGLGLG",
    "LGLGGL",
    "LGGLGL"};

static bool isDigits(const String &s)
{
  for (int i = 0; i < (int)s.length(); i++)
  {
    if (s[i] < '0' || s[i] > '9')
      return false;
  }
  return true;
}

static int ean13ChecksumDigit(const String &first12)
{
  // first12 must be 12 digits
  int sum = 0;
  for (int i = 0; i < 12; i++)
  {
    int d = first12[i] - '0';
    // positions are 1..12; even positions weight 3 in EAN-13
    // i=0 => pos1 => weight1
    int pos = i + 1;
    sum += (pos % 2 == 0) ? (3 * d) : d;
  }
  int mod = sum % 10;
  return (mod == 0) ? 0 : (10 - mod);
}

static String normalizeEan13(String digits)
{
  digits.trim();

  // Accept 12 digits => compute checksum and append
  if (digits.length() == 12 && isDigits(digits))
  {
    int cd = ean13ChecksumDigit(digits);
    digits += char('0' + cd);
    return digits;
  }

  // Accept 13 digits => optionally fix checksum if wrong
  if (digits.length() == 13 && isDigits(digits))
  {
    String first12 = digits.substring(0, 12);
    int cd = ean13ChecksumDigit(first12);
    if ((digits[12] - '0') != cd)
    {
      digits[12] = char('0' + cd);
    }
    return digits;
  }

  // Anything else: return empty => draw placeholder
  return "";
}
static void drawEan13BarcodeHorizontal(int x, int y, int w, int h, const String &rawDigits)
{
  String digits = normalizeEan13(rawDigits);
  if (digits.length() != 13)
  {
    sprite.setTextColor(TFT_WHITE, TFT_BLACK);
    sprite.drawString("INVALID", x + 4, y + 10, 1);
    return;
  }

  String bits;
  bits.reserve(95);
  bits += "101";

  int first = digits[0] - '0';
  const char *parity = PARITY[first];

  for (int i = 0; i < 6; i++)
  {
    int d = digits[1 + i] - '0';
    if (parity[i] == 'L')
      bits += L_CODE[d];
    else
      bits += G_CODE[d];
  }
  bits += "01010";
  for (int i = 0; i < 6; i++)
  {
    int d = digits[7 + i] - '0';
    bits += R_CODE[d];
  }
  bits += "101";

  const int modules = 95;
  int barW = w - 16;
  int guardW = w - 10;

  for (int i = 0; i < modules; i++)
  {
    bool isBar = (bits[i] == '1');
    if (!isBar)
      continue;

    bool isGuard =
        (i < 3) ||
        (i >= 45 && i < 50) ||
        (i >= 92);

    int ww = isGuard ? guardW : barW;
    int barY = y + (i * h) / modules;
    int barH = ((i + 1) * h) / modules - (i * h) / modules;
    sprite.fillRect(x, barY, ww, barH, TFT_WHITE);
  }

  // Digits vertical — spaced evenly
  // Create tiny sprite for rotated text
  sprite.setTextColor(TFT_WHITE, TFT_BLACK);
  int digitX = x + w - 10;
  for (int i = 0; i < 13; i++)
  {
    int digitY = y + (i * h) / 14 + 4;
    String ch = digits.substring(i, i + 1);
    sprite.drawString(ch, digitX, digitY, 1);
  }
}

// ===================== UI drawing =====================
// Croatian number format: 3.000,75 instead of 3,000.75
static String formatPriceCro(String price)
{
  // price comes as "3.75" or "25000.00"
  float val = price.toFloat();

  // Split into whole and decimal parts
  long whole = (long)val;
  int decimals = (int)round((val - whole) * 100);
  if (decimals < 0)
    decimals = -decimals;

  // Format whole part with . as thousands separator
  String wholeStr = String(whole);
  String formatted = "";
  int len = wholeStr.length();
  for (int i = 0; i < len; i++)
  {
    if (i > 0 && (len - i) % 3 == 0)
      formatted += ".";
    formatted += wholeStr[i];
  }

  // Add decimal part with , as separator
  String decStr = String(decimals);
  if (decimals < 10)
    decStr = "0" + decStr;
  formatted += "," + decStr;

  return formatted;
}

static void drawPriceTag(const ProductData &p)
{
  sprite.fillSprite(TFT_BLACK);

  // --- Header row ---
  sprite.setTextColor(TFT_WHITE, TFT_BLACK);
  sprite.drawString(p.name, 20, 10, 4);

  // IDD small, right side
  sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
  String idText = "IDD: " + p.id_display;
  int idW = sprite.textWidth(idText, 2);
  sprite.drawString(idText, 530 - idW, 18, 2);

  sprite.drawFastHLine(20, 42, 516, TFT_DARKGREY);

  // --- Right side: Barcode ---
  drawEan13BarcodeHorizontal(556, 0, 84, 180, p.barcode);

  // --- Left side: Price area ---
  String unit = p.unit.length() > 0 ? p.unit : "KOM";
  String priceFormatted = formatPriceCro(p.price);

  if (p.discount_price.length() > 0)
  {
    // CIJENA TREN label + old price crossed out
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("PROSLA CIJENA: ", 20, 46, 2);
    sprite.setTextColor(TFT_LIGHTGREY, TFT_BLACK);
    String oldPrice = priceFormatted + " EUR";
    sprite.drawString(" " + oldPrice, 120, 46, 2);
    int oldW = sprite.textWidth(oldPrice, 2);
    sprite.drawFastHLine(125, 46 + 7, oldW, TFT_RED);

    // POPUST DO DATUM/ISTEKA
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("POPUST:", 20, 62, 2);
    sprite.setTextColor(TFT_RED, TFT_BLACK);
    sprite.drawString("-" + p.discount_per + "%  do " + p.discount_end, 80, 62, 2);

    // CIJENA SNIŽENA - big discounted price with comma fix
    sprite.setTextColor(TFT_WHITE, TFT_BLACK);
    String discFormatted = formatPriceCro(p.discount_price);
    int commaPos = discFormatted.indexOf(',');
    String beforeComma = discFormatted.substring(0, commaPos);
    String afterComma = discFormatted.substring(commaPos + 1);
    sprite.drawString(beforeComma, 20, 92, 6);
    int bw = sprite.textWidth(beforeComma, 6);
    sprite.drawString(",", 20 + bw, 108, 4);
    int cw = sprite.textWidth(",", 4);
    sprite.drawString(afterComma, 20 + bw + cw, 92, 6);
    int newW = bw + cw + sprite.textWidth(afterComma, 6);
    sprite.setTextColor(TFT_LIGHTGREY, TFT_BLACK);
    sprite.drawString("EUR", 20 + newW + 10, 110, 4);

    // Right column - Najniža cijena u 30 dana
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("Najniza cijena u 30 dana:", 340, 46, 2);
    sprite.setTextColor(TFT_WHITE, TFT_BLACK);
    sprite.drawString(formatPriceCro(p.lowest_price) + " EUR", 340, 60, 2);

    // CIJENA PO KOM/KG/L
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("CIJENA PO " + unit + ":", 20, 140, 2);
    sprite.setTextColor(TFT_WHITE, TFT_BLACK);
    sprite.drawString(formatPriceCro(p.pricePerKg) + " EUR/" + unit, 20, 154, 2);

    // Ažurirano
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("Azurirano: " + p.updated, 340, 160, 2);
  }
  else
  {
    // Big price with comma fix
    sprite.setTextColor(TFT_WHITE, TFT_BLACK);
    int commaPos = priceFormatted.indexOf(',');
    String beforeComma = priceFormatted.substring(0, commaPos);
    String afterComma = priceFormatted.substring(commaPos + 1);
    sprite.drawString(beforeComma, 20, 62, 6);
    int bw = sprite.textWidth(beforeComma, 6);
    sprite.drawString(",", 20 + bw, 78, 4);
    int cw = sprite.textWidth(",", 4);
    sprite.drawString(afterComma, 20 + bw + cw, 62, 6);
    int priceW = bw + cw + sprite.textWidth(afterComma, 6);
    sprite.setTextColor(TFT_LIGHTGREY, TFT_BLACK);
    sprite.drawString("EUR", 20 + priceW + 10, 80, 4);

    // CIJENA PO KOM/KG/L
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("CIJENA PO " + unit + ":", 20, 130, 4);
    sprite.setTextColor(TFT_WHITE, TFT_BLACK);
    sprite.drawString(formatPriceCro(p.pricePerKg) + " EUR/" + unit, 20, 155, 4);

    // Ažurirano
    sprite.setTextColor(TFT_DARKGREY, TFT_BLACK);
    sprite.drawString("Azurirano: " + p.updated, 395, 160, 2);
  }

  // Push to LCD
  lcd_PushColors_rotated_90(0, 0, W, H, (uint16_t *)sprite.getPointer());
}
// ===================== Boot / panel =====================
static void panelBlankAndBacklightOff()
{
  pinMode(TFT_BL, OUTPUT);
  digitalWrite(TFT_BL, LOW); // OFF
  // Clear panel (portrait coords in driver)
  lcd_fill(0, 0, 180, 640, 0x0000);
}

static void backlightOn()
{
  digitalWrite(TFT_BL, HIGH);
}

void handleUpdate()
{

  String body = server.arg("plain");
  Serial.println("Received: " + body);

  {
    StaticJsonDocument<64> clearDoc;
    if (deserializeJson(clearDoc, body) == DeserializationError::Ok && clearDoc["clear"] == true)
    {
      panelBlankAndBacklightOff();
      g_hasDrawnOnce = false;
      g_lastHash = 0;
      prod.begin("product", false);
      prod.clear();
      prod.end();
      server.send(200, "application/json", "{\"success\":true}");
      return;
    }
  }

  ProductData incoming;
  if (!parseProductJson(body, incoming))
  {
    server.send(400, "application/json", "{\"success\":false}");
    return;
  }

  uint32_t h = hashProduct(incoming);
  if (h == g_lastHash)
  {
    server.send(200, "application/json", "{\"success\":true}");
    return;
  }

  g_lastHash = h;
  g_current = incoming;

  prod.begin("product", false);
  prod.putString("id", g_current.id);
  prod.putString("id_display", g_current.id_display);
  prod.putString("name", g_current.name);
  prod.putString("price", g_current.price);
  prod.putString("pricePerKg", g_current.pricePerKg);
  prod.putString("barcode", g_current.barcode);
  prod.putString("updated", g_current.updated);
  prod.putString("discount_per", g_current.discount_per);
  prod.putString("discount_price", g_current.discount_price);
  prod.putString("lowest_price", g_current.lowest_price);
  prod.putString("discount_end", g_current.discount_end);
  prod.putString("unit", g_current.unit);
  prod.end();
  drawPriceTag(g_current);

  if (!g_hasDrawnOnce)
  {
    backlightOn();
    g_hasDrawnOnce = true;
  }
  server.send(200, "application/json", "{\"success\":true}");
}

static void handleSave()
{
  String ssid = server.arg("ssid");
  String pass = server.arg("pass");
  String serverip = server.arg("serverip");

  prefs.begin("WiFi", false);
  prefs.putString("ssid", ssid);
  prefs.putString("pass", pass);
  prefs.putString("serverip", serverip);
  prefs.end();
  drawConnectingScreen();
  bool connected = wifiEnsureConnected(ssid, pass, 8000);
  if (connected == true)
  {
    server.sendHeader("Location", "/success");
  }
  else
  {
    server.sendHeader("Location", "/error");
    drawSetupScreen();
  }
  server.send(303);
}

static void handleSuccess()
{
  server.send(200, "text/html",
              R"(
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; min-height:100vh; background:#1a001f; display:flex; align-items:center; justify-content:center; font-family:sans-serif;">
  <div style="background:#280030; border:1px solid #5a1a65; border-radius:16px; padding:2.5rem; width:90%; max-width:400px;">
    <h2 style="color:#ec8eec; text-align:center; margin:0 0 0.5rem 0;">DigiPrices</h2>
    <p style="color:#a06aaa; text-align:center; font-size:0.85rem; margin:0 0 1rem 0;">Connect display to your network</p>
    <div style="background:rgba(61,214,140,0.1); border:1px solid #3dd68c; border-radius:7px; padding:0.65rem; margin-bottom:1rem; text-align:center;">
      <span style="color:#3dd68c; font-size:0.85rem;">Success! Rebooting..</span>
    </div>
  </div>
</body>
</html>
  )");
  delay(3000);
  ESP.restart();
}

void handlePortal()
{
  server.send(200, "text/html",
              R"(
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; min-height:100vh; background:#1a001f; display:flex; align-items:center; justify-content:center; font-family:sans-serif;">
  <div style="background:#280030; border:1px solid #5a1a65; border-radius:16px; padding:2.5rem; width:90%; max-width:400px;">
    <h2 style="color:#ec8eec; text-align:center; margin:0 0 0.5rem 0;">DigiPrices</h2>
    <p style="color:#a06aaa; text-align:center; font-size:0.85rem; margin:0 0 1.5rem 0;">Connect display to your network</p>
    <form method="POST" action="/save">
      <label style="color:#a06aaa; font-size:0.82rem;">SSID</label>
      <input type="text" name="ssid" placeholder="Enter WiFi name" style="width:100%; padding:0.65rem; margin:0.4rem 0 1rem 0; background:#1a001f; border:1px solid #5a1a65; border-radius:7px; color:#f5e6f5; font-size:0.9rem; outline:none; box-sizing:border-box;">
      <label style="color:#a06aaa; font-size:0.82rem;">Password</label>
      <input type="password" name="pass" placeholder="Enter WiFi password" style="width:100%; padding:0.65rem; margin:0.4rem 0 1.5rem 0; background:#1a001f; border:1px solid #5a1a65; border-radius:7px; color:#f5e6f5; font-size:0.9rem; outline:none; box-sizing:border-box;">
      <label style="color:#a06aaa; font-size:0.82rem;">Server</label>
      <input type="text" name="serverip" placeholder="192.168.1.x" style="width:100%; padding:0.65rem; margin:0.4rem 0 1.5rem 0; background:#1a001f; border:1px solid #5a1a65; border-radius:7px; color:#f5e6f5; font-size:0.9rem; outline:none; box-sizing:border-box;">
      <input type="submit" value="Connect" style="width:100%; padding:0.75rem; background:#ec8eec; color:#fff; border:none; border-radius:7px; font-size:0.9rem; font-weight:600; cursor:pointer;">
    </form>
  </div>
</body>
</html>
  )");
}

static void handlePortalError()
{
  server.send(200, "text/html",
              R"(
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; min-height:100vh; background:#1a001f; display:flex; align-items:center; justify-content:center; font-family:sans-serif;">
  <div style="background:#280030; border:1px solid #5a1a65; border-radius:16px; padding:2.5rem; width:90%; max-width:400px;">
    <h2 style="color:#ec8eec; text-align:center; margin:0 0 0.5rem 0;">DigiPrices</h2>
    <p style="color:#a06aaa; text-align:center; font-size:0.85rem; margin:0 0 1rem 0;">Connect display to your network</p>
    <div style="background:rgba(247,95,95,0.1); border:1px solid #f75f5f; border-radius:7px; padding:0.65rem; margin-bottom:1rem; text-align:center;">
      <span style="color:#f75f5f; font-size:0.85rem;">Connection failed. Try again!</span>
    </div>
    <form method="POST" action="/save">
      <label style="color:#a06aaa; font-size:0.82rem;">SSID</label>
      <input type="text" name="ssid" placeholder="Enter WiFi name" style="width:100%; padding:0.65rem; margin:0.4rem 0 1rem 0; background:#1a001f; border:1px solid #5a1a65; border-radius:7px; color:#f5e6f5; font-size:0.9rem; outline:none; box-sizing:border-box;">
      <label style="color:#a06aaa; font-size:0.82rem;">Password</label>
      <input type="password" name="pass" placeholder="Enter WiFi password" style="width:100%; padding:0.65rem; margin:0.4rem 0 1.5rem 0; background:#1a001f; border:1px solid #5a1a65; border-radius:7px; color:#f5e6f5; font-size:0.9rem; outline:none; box-sizing:border-box;">
      <label style="color:#a06aaa; font-size:0.82rem;">Server</label>
      <input type="text" name="serverip" placeholder="192.168.1.x" style="width:100%; padding:0.65rem; margin:0.4rem 0 1.5rem 0; background:#1a001f; border:1px solid #5a1a65; border-radius:7px; color:#f5e6f5; font-size:0.9rem; outline:none; box-sizing:border-box;">
      <input type="submit" value="Connect" style="width:100%; padding:0.75rem; background:#ec8eec; color:#fff; border:none; border-radius:7px; font-size:0.9rem; font-weight:600; cursor:pointer;">
    </form>
  </div>
</body>
</html>
  )");
}

static void registerWithServer(String serverip)
{
  String url = "http://" + serverip + "/DigiPrices/api/register.php"; // REMEBER TO REMOVE HARDOCED LINK!!!!
  String payload = "{\"ip\":\"" + WiFi.localIP().toString() + "\"}";
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  int http_code = http.POST(payload);
  http.end();
  Serial.println("Server IP: " + serverip);
  Serial.println("URL: " + url);
  Serial.println("Registration response: " + String(http_code));
}

void drawIpScreen()
{
  backlightOn();
  sprite.fillSprite(TFT_BLACK);
  sprite.drawString("DigiPrices connect!", 10, 80, 4);
  sprite.drawString("IP:" + WiFi.localIP().toString(), 10, 120, 4);
  lcd_PushColors_rotated_90(0, 0, W, H, (uint16_t *)sprite.getPointer());
}

void drawSetupScreen()
{
  backlightOn();
  sprite.fillSprite(TFT_BLACK);
  sprite.drawString("DigiPrices Setup", 10, 20, 4);
  sprite.drawString("Connect to WiFi: DigiPrices-Setup-Network", 10, 60, 4);
  sprite.drawString("Then open browser at: " + WiFi.softAPIP().toString(), 10, 100, 4);
  lcd_PushColors_rotated_90(0, 0, W, H, (uint16_t *)sprite.getPointer());
}

void drawConnectingScreen()
{
  backlightOn();
  sprite.fillSprite(TFT_BLACK);
  sprite.drawString("Trying to connect...", 10, 80, 4);
  lcd_PushColors_rotated_90(0, 0, W, H, (uint16_t *)sprite.getPointer());
}
// ===================== Main =====================
void setup()
{

  pinMode(PIN_BUTTON_1, INPUT_PULLUP);
  Serial.begin(115200);
  axs15231_init();
  panelBlankAndBacklightOff();
  sprite.createSprite(W, H);
  sprite.setSwapBytes(1);

  prefs.begin("WiFi", false);
  // prefs.clear(); //for clearing the stored password and ssid
  String ssid = prefs.getString("ssid", "");
  String pass = prefs.getString("pass", "");
  String serverip = prefs.getString("serverip", "");
  prefs.end();

  if (ssid == "")
  {
    Serial.println("No credentials saved!");
    WiFi.softAP("DigiPrices-Setup-Network");
    Serial.println("AP IP: " + WiFi.softAPIP().toString());
    server.on("/", HTTP_GET, handlePortal);
    server.on("/save", HTTP_POST, handleSave);
    server.on("/success", HTTP_GET, handleSuccess);
    server.on("/error", HTTP_GET, handlePortalError);
    server.begin();
    drawSetupScreen();
  }
  else
  {
    Serial.println("SSID: " + ssid + " PASS: " + pass);
    WiFi.mode(WIFI_STA);
    drawConnectingScreen();
    bool connected = wifiEnsureConnected(ssid, pass, 8000);
    if (connected == false)
    {
      prefs.begin("WiFi", false);
      prefs.clear();
      prefs.end();
      WiFi.softAP("DigiPrices-Setup-Network");
      server.on("/error", HTTP_GET, handlePortalError);
      server.on("/save", HTTP_POST, handleSave);
      server.on("/success", HTTP_GET, handleSuccess);
      server.begin();
      drawSetupScreen();
    }
    else
    {

      prod.begin("product", true);
      String savedName = prod.getString("name", "");
      if (savedName != "")
      {
        ProductData saved;
        saved.id = prod.getString("id", "");
        saved.id_display = prod.getString("id_display", "");
        saved.name = prod.getString("name", "");
        saved.price = prod.getString("price", "");
        saved.pricePerKg = prod.getString("pricePerKg", "");
        saved.barcode = prod.getString("barcode", "");
        saved.updated = prod.getString("updated", "");
        saved.discount_per = prod.getString("discount_per", "");
        saved.discount_price = prod.getString("discount_price", "");
        saved.lowest_price = prod.getString("lowest_price", "");
        saved.discount_end = prod.getString("discount_end", "");
        saved.unit = prod.getString("unit", "");
        prod.end();
        drawPriceTag(saved);
      }
      else
      {
        prod.end();
        drawIpScreen();
      }

      WiFi.softAPdisconnect(true);
      registerWithServer(serverip);
      Serial.print("[WiFi] Connected! IP: ");
      Serial.println(WiFi.localIP());
      server.on("/update", HTTP_POST, handleUpdate);
      server.begin();
      Serial.println("[BOOT] Ready (waiting for updates)");
    }
  }
}

static uint32_t lastPoll = 0;

void loop()
{
  if (digitalRead(PIN_BUTTON_1) == LOW)
  {
    if (buttonPressStart == 0)
    {
      buttonPressStart = millis();
    }
    else if (millis() - buttonPressStart > 5000)
    {
      prefs.begin("WiFi", false);
      prefs.clear();
      prefs.end();
      prod.begin("product", false);
      prod.clear();
      prod.end();
      ESP.restart();
    }
  }
  else
  {
    buttonPressStart = 0;
  }
  server.handleClient();
  delay(5);
}