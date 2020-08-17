# yamori4webAuthn
Web Authentication Sample Application For PHP.

<img src="./_readMe/japaneseFlag.png" width="40"/>Click [here](https://github.com/yamori4/yamori4webAuthn/blob/master/README_japanese.md) for Japanese page. 



1. [Summary](#Summary)
2. [Demo Site](#Demo-Site)
3. [Screen Capture](#Screen-Capture)
4. [Installation](#Installation)
5. [Usage](#Usage)
6. [Relying On The Open Sources](#Relying-On-The-Open-Sources)
7. [License](#License)
8. [Notes](#Notes)
9. [Author](#Author)



## Summary

This application is a sample for the WebAuthn ([Web Authentication](https://www.w3.org/TR/webauthn/)), which is a component of the FIDO2.

I developed this application to use WebAuthn in the LAMP (or XAMPP) environment.

 I hope this app will help you deploy remote work, keeping in mind the recent corona virus pandemic.



## Demo Site
[https://endlesshard.work/yamori4webAuthn/index.html](https://endlesshard.work/yamori4webAuthn/index.html)

<img src="./_readMe/demo.jpg" width="600"/> 



## Screen Capture

 <img src="./_readMe/authnScreen.jpg" height="300"/>

 <img src="./_readMe/authnSuccessScreen.jpg" height="230px"/>



## Installation

* **Requirement**

  * Runtime environment for PHP

    <small>* Some methods will not work unless you use PHP 7.4 or above, so it is recommended to use a version higher than that.</small>

  * Runtime environment for MySQL

  * Popular web browsers

    <small>e.g.) Chrome, Firefox, Edge,  and so on, but IE does not support WbAuthn.</small>

  * The Authenticator such as "FIDO key" or "Windows Hello".

  

* **Build middleware**

  Please refer to the XAMPP construction procedure summarized [here](https://github.com/yamori4/objectOriented/blob/master/_readMe/installXampp.md) (But only in Japanese language).

  By the way, this document assumes the XAMPP environment, but it also works in the LAMP environment.

  

* <b>Download and deploy source code</b>

  <small>* It is assumed that XAMPP has been installed and is installed in "C:\xampp".</small>

1. Download the source code for this sample application from [GitHub](https://github.com/yamori4/yamori4webAuthn). In this example, download the zip file. Click "Download ZIP" from "Clone or download" on the GitHub page. ![alt](./_readMe/downloadZip.jpg)

     

2. Unzip the downloaded zip file.

     

3. The name of the unzipped file is "yamori4webAuthn-master", so change it to "yamori4webAuthn".

     

4. Place the unzipped file under "C:\xampp\htdocs". It is OK if the directory structure is as follows.

 ```
 C:
 └xampp
   └httocs
     └yamori4webAuthn
       ├─assertion
       ├─attestation
       ├─config
       ├─db
       ├─log
       ├─oss
       ├─sql(create_table)
       ├─src
       ├─util
       ├─deleteDb.php
       ├─index.html
       └─loginSuccess.php
 ```

5. Build is completed.  So let's run it as a web service and check a simple operation. Click "C:\xampp\xampp-control.exe" to start the XAMPP control panel.

   

6. Start the Apache. Click "Start" button of the Apache.

<img src="./_readMe/controlPanel.jpg" />



7.  It has started.

<img src="./_readMe/wakeupApache.jpg" />




8. Launch any web browser. In this example, using Chrome. Enter "https://localhost/yamori4webAuthn/index.html" in the address bar of your web browser. If you do not set the SSL certificate properly with XAMPP, a privacy error will be displayed on the browser, but This time, make sure that the connection destination is your PC (localhost) before connecting. If you use Chrome, click the "Advanced" button and then "Proceed to localhost (unsave)". By the way, the WebAuthn cannot be used with "http", it must be "https".

<img src="./_readMe/privacyError.jpg" height="500px"/> 



9. The following screen will be displayed.（The WebAuthn doesn't work yet because I haven't set up the database.）

<img src="./_readMe/indexScreen.jpg" />




* **Create table in database**

  It is a prerequisite that XAMPP has been installed.

1. Start the XAMPP control panel.

   

2. Start the Apache and the MySQL by clicking the "Start" button.

<img src="./_readMe/xamppStart.jpg" />

<img src="./_readMe/xamppStarted.jpg" />



3. Click the "Admin" button of MySQL to open the phpMyAdmin console in your web browser. Enter the MySQL account ID and password in the console of phpMyAdmin and log in.

<img src="./_readMe/phpMyAdminStart.jpg" />



4. Create a new database. In this example, the database name is "test".

<img src="./_readMe/phpMyAdminCreateDB.jpg" />



5. Create a table in the database "test".Enter the SQL query of the file under "yamori4webAuthn/sql(create_table)" in the query input field of the [SQL] tab and click the [Go] button. In this app, you need to create a "user" table and a "web_authn" table.

<img src="./_readMe/createTable.jpg" width="700px" />

<img src="./_readMe/createdTable.jpg" width="700px" />



* **Application settings**

  * Open the "yamori4webAuthn/config/config.ini" file with a text editor and change each parameter as needed.

    * **test_mode** → Set true/false to switch the test mode ON/OFF.  The behavior is slightly different in test mode. In test mode, output the log for debugging to the log file, and you can delete the user information and WebAuthn key information registered in the database by accessing "https://localhost/yamori4webAuthn/deleteDb.php".
    * **relying_party**  → This is the domain name of the website that uses WebAuthn. If you use this app in your local environment, you can set "localhost". The value of this is used as a parameter for WebAuthn authentication.
    * **db_data_source_name** → Database data source name
    * **db_user_name** → Database login user name.
    * **db_password** →  Database password.
    

<img src="./_readMe/editConfig.jpg"/>



## Usage

1. In case of immediately after setting, restart the Apache and the MySQL for now.

   

2. Open the built web page "https://localhost/yamori4webAuthn/index.html" using a popular web browser (Chrome, Firefox, Edge,  and so on).

   

3. Register the authenticator. Enter any login ID and click the "Register" button.

   <img src="./_readMe/use_inputLoginId.jpg"/>

   <small>Examples of authenticators include the types that are used by connecting to USB, such as the image below, and Windows Hello. If you use a smartphone that supports fingerprint, face authentication or other, you can use biometric authentication by linking them with FIDO2 authentication. Some of the authentication devices support NFC, and you can use them with the NFC communication function of your smartphone.
   You can also use an authenticator that can be connected to USB Type-C by connecting it to the smartphone terminal. By the way, the situation is complicated at present on iOS devices (as of May 2020), so please check the support status yourself.</small>

   <img src="./_readMe/authenticator.jpg" width=300/>

   

4. A request for identity verification is requested from a web browser. In response to this request, you authenticate using an authenticator.

   <img src="./_readMe/registerScreen.jpg"/>

   

5. Authentication for registration was successful.

   <img src="./_readMe/registrationSuccess.jpg"/>

   

6. Next, authenticate your login. Enter the login ID you set up earlier and click the "Assertion" button.

   <img src="./_readMe/use_assertion.jpg"/>

   

7. A request for identity verification is requested from a web browser. In response to this request, you authenticate using an authenticator.

   <img src="./_readMe/authnScreen.jpg"/>

   

8. Authentication was successful, and the web page transitions.

   <img src="./_readMe/authnSuccessScreen.jpg"/>

   

   If you click "Logout", the session information will be deleted and you will be logged out to return to the authentication screen.

   If the screen after login is displayed without authentication, it will be as follows.

   <img src="./_readMe/notAuthenticated.jpg" height="200px"/>



#### About other functions

* **Logging**

  The log output destination is under "yamori4webAuthn/config/".

  The log output destination is defined inside the "yamori4webAuthn/util/log.php" file. You can change the log output destination by changing the settings in this file.

  

* **Delete data in database**

  <small>This function can only be used if "test_mode = true" is set in the "yamori4webAuthn/config/config.ini file".</small>

  You can delete the user information and WebAuthn key information recorded in the MySQL database by entering "https://localhost/yamori4webAuthn/deleteDb.php" in the address bar of the WEB browser while in the test mode. This feature is useful for testing, but if you use this app for commercial use <u>you had better remove this feature</u>.

  <img src="./_readMe/deleteDb.jpg" height="200px"/>



## Relying On The Open Sources

* [WebAuthnDemo](https://github.com/google/webauthndemo/blob/master/src/main/webapp/js/webauthn.js)
  *&copy; 2017 Google Inc. All Rights Reserved.*
  *Released under the Apache License, Version 2.0*
  *see http://www.apache.org/licenses/*


* [CBOR encoder for PHP](https://github.com/2tvenom/CBOREncode)
  *&copy; 1999 - 2012 The PHP Group. All rights reserved.*
  *Released under the PHP License, version 3.01*
  *see https://www.php.net/license/index.php*
  *This product includes PHP, freely available from https://www.php.net/*

#### Acknowledgment

I used the above open sources to develop this sample WebAuthn application.
I would like to thank the developers of these programs sincerely.



## License

"yamori4webAuthn" is under [MIT license](https://opensource.org/licenses/mit-license.php).
© 2020 Yamori4. All rights reserved.



## Notes

- When you use this app, please keep in mind that [the open source](#Relying On The Open Sources) used in this app is not Released under [the MIT license](https://opensource.org/licenses/mit-license.php). The open sources used in this app are put together under "yamori4webAuthn/oss". So you can avoid the license restrictions if you replace their source code with another.
- In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software. This notice is also included in the MIT license.
- This sample application does not completely implement the specifications of  WebAuthn. Specifically, I haven't implemented the FIDO Alliance Metadata Service (MDS) at all, and I don't fully understand the Trusted Platform Module (TPM). But I think this app is of sufficient quality for general use.
- I'm using a ".htaccess" file in the source code, which is actually not good (".htaccess" is deprecated). However, in this sample app, I intentionally used ".htaccess" to minimize Apache settings.
- The use of this application by anti-social forces and their parties is forbidden.
- If you would like to use this app for enterprise, please contact [me](<mailto: yamori4.113@gmail.com>). Because I want to know what kind of service and how this app will be used.
  

## Author

​	**[yamori4](<mailto: yamori4.113@gmail.com>)**

<small>I have been a software engineer for 10 years. I am japanese. I am not good at English. If my English is messed up, I am sorry.</small>



Thank you!
