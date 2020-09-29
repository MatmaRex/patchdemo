With Patch Demo, you too can spin up a MediaWiki instance running a particular patch from Wikimedia Gerrit. (To my knowledge, the idea was first described in [T76245](https://phabricator.wikimedia.org/T76245).)

This project is not secure. You should only install it in disposable virtual machines, and maybe have some monitoring in place in case someone starts mining bitcoin on them.

While I've made token effort to avoid remote code execution vulnerabilities, the whole point of the project is to allow your users to execute arbitrary code on the demo wikis, and the wikis are not isolated.

Features
----
* Create a public wiki with [bundled extensions/skins](./repositories.txt)
* Use a specific release or WMF version
* Apply any number of patches to MediaWiki, extensions or skins
* Require that patches have V+2 review (token security effort)

Limitations
----
* Runs MediaWiki only – no RESTBase and other fancy stuff

Setup
----
Tested on Ubuntu 18.04 and Debian 10.

* Put all these files in `/var/www/html`
* Run `sudo setup.sh`
* Visit http://localhost in your browser

FAQ
----
**Can you delete it when you are done with it?**

Yes. For any wiki you create, you will see a `Delete` link in the `Action` column of this table: https://patchdemo.wmflabs.org/

**How long do the Patch demo wiki instances last for?**

There is no definitive time after which wikis will automatically be deleted. With this said, we make no guarantees about how long they will continue to exist. A Patch demo wiki you've created could be deleted if we need to free up disk space to create space for new ones.

**Can Patch demo wikis be named?**

Wikis can not been named *within* Patch demo. Patch demo wikis currently inherit the name of the patch on which they are built.

**Is it possible to add extensions that are in development?**

#TODO

**Is it possible to add patches for extension not just core? And skins?**

#TODO
