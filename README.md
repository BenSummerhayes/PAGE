
Introduction….

Hello and Welcome to “PAGE” (PHP API handler Generator & Editor) V1.0

This only works with PHP(5.5+), Apache and MYSQL.

Sessions must be enabled - if this is an issue then simply comment out the password access control code on and around lines 7 - 40 on the API_editor.php file.

This is a no nonsense, lightweight, no frills RESTful API tool written for PHP.

This tool will enable you to created, edit and handle API’s. 

It has a build in API Key and Security Token handler system.

Furthermore it has an inbuilt version control system.

This tool also offers a tool for creating and testing Database Connections (for use in conjunction with your API).

So far this only works with MYSQL (PDO) but NOT MSSQL. This will come later but please feel free to have a stab at it yourself (I’ve left gaps for it in the code)

To use simply go to the folder (default name PAGE_V1/apis) and click on the API_editor.php file

The password to log into the edit area is: qq obviously you will want to change this (on or around line 10 on the API_editor.php file - “$apiEditAreaPassword” variable)

You can change the PAGE_V1 folder name to anything you want but everything else within that folder must stay the same name and must NOT be moved around

I am yet to add any styling to the interface. This is will be completed for the next release.

ALL Constructive feedback is welcome!

Enjoy!

Ben Summerhayes



You MUST READ these simple setup instructions!!!

1. Copy the “PAGE_v1” folder and it’s contents to wherever you like onto your webserver

2. The file named “httaccess.txt” MUST be renamed to “.httaccess”


3. You will need to make these changes to your Apache Server so the htaccess file works as required (no biggy)

Linux Users:

Run this in your terminal to switch on the "mod_rewrite":

sudo a2enmod rewrite | sudo service apache2 restart

Next do this:

sudo nano /etc/apache2/apache2.conf

press ctrl W and search for "<Directory /var/www/>"

and below that make sure it reads:

    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted

ctrl X and SAVE it!

Finally run this:

sudo service apache2 restart

Job Done!


OR


MAC users:

sudo nano /etc/apache2/httpd.conf

uncomment the following lines:

#LoadModule rewrite_module libexec/apache2/mod_rewrite.so
#LoadModule php5_module libexec/apache2/libphp5.so

Make sure the following text is in the http config file:

<Directory "$HOME/Sites/">
Options Indexes MultiViews FollowSymLinks
AllowOverride All
Require all granted
</Directory>

ctrl X and SAVE it!

Finally run this:

sudo service apache2 restart

Job Done!


OR


Windows users:

…..Sorry but I’ve not had a chance to get all this set up and tested on a windows system. Feel free to have a stab at it yourself and share the knowledge!


4. If you experience permissions issues then deal with them as per normal (i.e. CHMOD them)

5. The password to log into the edit area is: qq obviously you will want to change this (on or around line 10 on the API_editor.php file - “$apiEditAreaPassword” variable)

End of setup instructions.



