# SMSArchiver

## What it is

SMSArchiver is a very simple and light web app that allows you to import your SMS dumps
to archive all your old conversations for further reading, just for fun. :)

It imports SMS dumps from 2 android apps: [SMS Backup](https://play.google.com/store/apps/details?id=cn.menue.smsbackup.international) and [SMS Backup&Restore](https://play.google.com/store/apps/details?id=com.riteshsahu.SMSBackupRestore)

It also import Google Contact CSV dumps.

It also have a nice UI (according to me) inspired by Google design for circles with contact initial in it :)

![SMSArchiver demo](http://i.imgur.com/iUqjs7z.png)

## How it's done

It's a really simple and light app. Here is what the files do:

* index.php is the template for the UI
* initialImport.php is a batch script to import your dumps during install process
* lib/Views.php is the view/controller that allows the web interface to work
* lib/Import.php is a controller that handles all the dump import work
* lib/Model.php is the database acessor

## How to use it

Note that SMSArchiver is not really an easy plug'n'play software that you just download and deploy.
It almost is, but I didn't had time to made it that easy to use and you still have to get
your hands dirty a little bit.

First, download a zip of the repo and put it on a webserver with PHP and MySQL (works with PHP 5.4 but also
probably previous versions, it's just simple PHP OOP with PDO), and MySQL and SQLlite PDO extensions.

### Configure it

1. Create a database...
2. You will need to configure it the hard way (sorry). You will have to edit:
	* lib/Views.php : edit MY_NAME and optionally $MONTH_LONG, $MONTH_SHORT, MY_NUMBER, SMS_PER_PAGE, SMS_SUPPL
	* lib/Model.php : edit DB, USER, PASS, HOST, TBL_MESSAGES, TBL_CONTACTS (the tables will be created)
3. Dumps contains number that are formatted in very different ways. As provided, the cleaner it only remove
   caracters that are not 0 to 9 or +. You might want to uncomment the regex that replaces first 0 with you
   local international prefix to avoid having numbers with and without international prefix.
   To do so, edit the  function formatPhoneNumber in lib/Import.php
4. You can do some i18n by editing index.php to translate the static texts.

### Import you dumps the batch way (recommanded for first import only)

initialImport.php allows you to import your dumps the batch way. I recommand you to do this.

The file is quite easy to edit. Just edit various paths to match where you put your dumps and run it.
It will probably take a while so you should edit your PHP max_execution_time to give it some time to run.

The messages will first all be imported in the DB_IMPORT_TO table, then they will be filtred to remove duplicates
(because dumps often overlap) and inserted into DB_CLEAN_TO.

Finally all the contacts will be extracted from the DB_CLEAN_TO table and matched with a Google Contacts CSV.
If it can't find a match, the name of the contact will be it's phone number. All the contacts will be inserted in 
the DB_CONTACTS table.

It the DB_CLEAN_TO and DB_CONTACTS match the settings in Model.php, you're good to go.

Note that it is designed only to be used when importing dumps from an empty database. It will probably have
strange behavior with an already filled database.

### Import your dumps the UI way (recommanded when adding new dumps after first import)

Open the UI (just open a browser on the web folder containing the app) and use the import tool on
top of the page. Keep in mind the supported dump formats.

You will have to edit the contacts table manually to enter names however...

## Questions

I am aware that this is definitely not the easy-to-use app. The code could be better, the config could be
better, (I could have documented the code...), etc. It's a just-for-fun kind of project, not a very developped one.

If you want to use it in any way and have difficulties using it, don't hesitate to contact me, for example
add an issue on the repo.

## License

SMSArchiver is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

SMSArchiver is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along SMSArchiver.  If not, see <http://www.gnu.org/licenses/>

