With Patch Demo, you too can spin up a MediaWiki instance running a particular patch from Wikimedia Gerrit. (To my knowledge, the idea was first described at <https://phabricator.wikimedia.org/T76245>.)

This project is not secure. You should only install it in disposable virtual machines, and maybe have some monitoring in place in case someone starts mining bitcoin on them.

While I've made token effort to avoid remote code execution vulnerabilities, the whole point of the project is to allow your users to execute arbitrary code on the demo wikis, and the wikis are not isolated.

Features
----
* Create a public wiki with bundled extensions/skins
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
