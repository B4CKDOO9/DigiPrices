# DigiPrices Quick Overview
DigiPrices is a finals project for graduating from a Secondary Vocational School in Samobor. This is the source code for this project that I hope I will bring to life compleatly
at a later date. Since I have been sitting at this idea since 2021, theere has been a boom in electronicaly managed prices, but these onse are different and you may learn how. For
the future I have many more inovations that I would like to add to this display, but I lack knowledge in many fields so I hope one day I will employ a team so we can make this great.
<br>
**Jan Jagustović - Hardware**
<br>
**Filip Smolec - Software**
# Requerments
* LilyGo T Display S-3
* Xampp
* Arduino IDE
  <br>
# Instalation
## Display setup
Firstly you need to install xampp the version doesnt matter. After you install xampp, the next step is to setup the display. Every file is in icluded in repository that
**NEEDS TO BE IN THE SAME PROJECT AS THE .INO FILE** when you are finished with adding the files to the project in Arduino IDE, you need to install libraries and board manager,
**DO NOT DEVIATE FROM VERSIONS**. 
<br>
### Libraries
* Arduinojson by Benoit Balanchon v(7.4.3)
* GFX Library for Arduino by Moon On Our Nation v(1.6.5)
* TFT_eSPI by Bodmer v(2.5.43)
* XPowersLib by Lewis He v(0.3.3)
### Board manager
* esp32 by Espressif Systems v(3.3.8)
  <br>
  
<p> After you have installed this dependancies, you will have to change the settings on the display, go to <b>TOOLS --> BOARD MANAGER --> ESP32S3DEV</b></p> 

### Settings for the display
<img width="406" height="665" alt="image" src="https://github.com/user-attachments/assets/b1a2f023-77b7-442e-ae7f-bc24ef70c0b7" />
<br>
<p>When you have finished with the display flash it <b style="color:red;">BEWARE OF LINE 652 in arduino ino file check the path in the xampp</b></p>

## Database and Website setup
<p>When you are done with this the next step is to setup xampp. There you can paste the files from github in the folder <b>Website</b>, to <b>File Manager --> This PC -->
local disk --> xampp --> htdocs --> paste </b>, with that done you are one step closer! Then you will need to import the given database from repository to <b>phpmyadmin</b>,
first create a new databse named <b>digiprices</b>, then open it <b>import --> choose file --> import </b></p>

## Firewall setup 
<p>Since you might have a problem with firewall i advise you to do this <b>Win key --> cmd --> run as administrator --> 
netsh advfirewall firewall add rule name="XAMPP Apache" dir=in action=allow program="C:\xampp\apache\bin\httpd.exe" enable=yes profile=private
</b>,this is to let the display communicate with the server</p>

## Task Scheduler
<p>Because this was kind of a raw project, without any frameworks you will need to add a task in task scheuer that checks if the discounts have expierd. To do this <b>Win key
--> Task Scheduler --> Create Basic Task --> Name --> Daily --> Start date (today) 00:00 hours, recur every 1 days --> Start a program --> program: C:\xampp\php\php.exe arguments: path to check_discount.php in XAMPP </b></p>
<br>

# How to use
