Etherpad-Lite HTML Export Script
================================

This is a simple script that exports your MySQL-backed Etherpad-Lite installation to static HTML 
files.  It retains none of the change history, nor the authors. The point is to make sure you have 
a static backup of the raw content.  There are ruby and php versions.

Configuration
-------------

You need to copy the `settings.yml.template` file to `settings.yml` and edit it to match your 
configuration.

PHP Instructions
----------------

Just run it:

```
php eplite-export.php
```

Ruby Instructions
-----------------

This script requires ruby and the bundler.

```
gem install bundler
bundle install
```

To run it, do:

```
ruby eplite-export.rb
```

Output
------

Running either script will create a timestamped folder in the `backup_dir` you specified 
in the `settings.yml` file.  This folder contains three things:

- a `pads` sub-folder with html files, each containing the raw content of a pad
- an index.html file that links to all the raw content files
- an server-toc.html that can serve as a "table of contents" page for the live server

Motivitation
------------

Our organization uses etherpad-lite as a critical part of our infrastructure. Etherpad-Lite has no 
export capability.  We wanted some way to export the pads to static HTML because we don't have a 
lot of confidence in the code - it crashes on us a lot.  Also, we wanted a table of contents for 
our etherpad install, but I don't know Node.js, so I made it in ruby instead.  However, then 
getting Ruby installed on the old Ubuntu server turned out to be a nightmare, so porting it to PHP 
was the quicker solution (ugh!).

Version History
---------------

v0.2 - 2012.02.08
 - Ported to PHP
 
v0.1 - 2012.02.07
 - first release for use on the server