Major Shimmie2 Fork
~~~~~~~~~~~~~~~~~~~
This distribution is a fork of Shish's Shimmie2 Imageboard, adding useful
new features, a complete list of which can be found on our web site.

At the moment, features are constantly under development, and thus it is
not a good idea to try running a production site with the code at this
time.

From Original README:
~~~~~~~~~~~~~~~~~~~~~
If there is a feature here, and not in the stable branch, that's probably
because the feature doesn't work yet :P


Requirements
~~~~~~~~~~~~
MySQL 4.1+
PHP 5.0+
GD or ImageMagick

There is no PHP4 support, because it lacks many useful features which make
shimmie development easier, faster, and less bug prone. PHP4 is officially
dead, if your host hasn't upgraded to at least 5, I would suggest switching
hosts. I'll even host galleries for free if you can't get hosting elsewhere
for whatever reason~


Installation
~~~~~~~~~~~~
1) Create a blank database
2) Unzip shimmie into a folder on the web host
3) Visit the folder with a web browser
4) Enter the location of the database
5) Click "install". Hopefully you'll end up at the welcome screen; if
   not, you should be given instructions on how to fix any errors~


Upgrade from 2.2.X
~~~~~~~~~~~~~~~~~~
Should be automatic, just unzip into a clean folder and copy across
config.php, images and thumbs folders from the old version. This
includes automatically messing with the database -- back it up first!


Upgrade from earlier versions
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
I very much recommend going via each major release in turn (eg, 2.0.6
-> 2.1.3 -> 2.2.4 -> 2.3.0 rather than 2.0.6 -> 2.3.0). While the basic
database and file formats haven't changed *completely*, it's different
enough to be a pain.


Contact
~~~~~~~
(No email yet!)
(No web site yet!)
https://www.assembla.com/spaces/shimmie/tickets -- bug tracker

Acknowledgements
~~~~~~~~~~~~~~~~
Most of original code written by Shish [http://shishnet.org/]
Based on the Danbooru concept [http://danbooru.donmai.us]

Disclaimer
~~~~~~~~~~
We make NO GUARANTEES that this software will work as planned, and are
NOT OBLIGATED to provide any support. However, if you ask nicely, we
may be able to help :)

Licence
~~~~~~~
All code is GPLv2 unless mentioned otherwise; ie, if you give shimmie to
someone else, you have to give them the source (which should be easy, as PHP
is an interpreted language...). If you want to add customisations to your own
site, then those customisations belong to you, and you can do what you want
with them.