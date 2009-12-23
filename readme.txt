=== Three Strikes and You're Out ===
Contributors: jammycakes
Donate link: http://www.jamesmckay.net/code/three-strikes/
Tags: comments, comment, spam
Requires at least: 2.0
Tested up to: 2.2
Stable tag: trunk

Closes comments across the board to IP addresses that are behaving badly.

== Description ==

Three Strikes and You're Out examines your Bad Behavior logs and your WordPress
spam queue and closes comments on all blog entries across the board to IP
addresses that have reported three or more attempts at mischief over the past
seven days.

It also has an additional logging feature and an API that allows other plugins
to register certain events as being mischievous.

== Installation ==

* Copy the file `three-strikes.php` to your `/wp-content/plugins` directory.
* Activate it in your WordPress dashboard.

== Configuration ==

By default, Three Strikes and You're Out closes comments right across the board
on your blog to any IP address that has attempted any mischief three or more
times in the past week. You can change these by editing the two defined
constants at the top of the plugin file, just below the copyright notice. There
are three such defines:

* `THREE_STRIKES_LIMIT` is the number of bad hits at which to block comments.
* `THREE_STRIKES_TIMEOUT` is the time in seconds after which bad hits are no
longer considered.
* `THREE_STRIKES_BB_STRICT` indicates that you should consider any "strict"
events recorded in the Bad Behavior log. These are events that are blocked when
Bad Behavior is using the "strict checking" option, but would be ignored
otherwise.

== The Three Strikes API ==

This plugin includes a number of hooks that allow other plugins to communicate
with it. These are implemented as actions and filters, so if Three Strikes is
not installed, your plugin will continue to function.

Full details can be found at

http://www.jamesmckay.net/code/three-strikes/api/

== Redistribution == 

Copyright (c) 2007 James McKay
http://www.jamesmckay.net/

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

== Reporting bugs ==

When reporting bugs, please provide me with the following information:

1. Which version of Three Strikes And You're Out you are using;
2. Which version of WordPress you are using;
3. The URL of your blog;
4. Which platform (Windows/IIS/PHP or Linux/Apache/MySQL/PHP) your server
   is running, and which versions of Apache and PHP you are using, if you
   know them;
5. The steps that need to be taken to reproduce the bug;
6. If possible, any relevant information on other plugins or configuration
   options that you may think appropriate.
   
See the following blog entry for further guidance on troubleshooting and
reporting problems:

http://www.jamesmckay.net/2007/04/how-to-report-issues-with-wordpress-plugins/

== For more information ==

For more information, please visit the plugin's home page:

http://www.jamesmckay.net/code/three-strikes/

The Mercurial repository and issue tracker for this plugin are at:

http://bitbucket.org/jammycakes/three-strikes/