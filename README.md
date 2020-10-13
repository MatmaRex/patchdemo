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
**Can you delete a wiki when you are done with it?**

Yes. For any wiki you create, you will see a `Delete` link in the `Action` column of the table of previously generated wikis on the main page. We advise you to delete the wikis you create when you are finished with them and/or when the patch you created the wiki to test is merged.

**How long do the Patch demo wiki instances last for?**

There is no definitive time after which wikis will automatically be deleted. With this said, we make no guarantees about how long they will continue to exist. A Patch demo wiki you've created could be deleted if we need to free up disk space to create space for new ones.

**Can Patch demo wikis be named?**

Wikis can not been named *within* Patch demo. Wikis are listed within Patch demo by the creator and the list of patches (potentially multiple) used to create it. They are also assigned a random hash, which becomes part of the URL. 

**Is it possible to add extensions that are in development?**

These will be considered on a case-by-case basis, but will generally be allowed as long as they don't interfere with other teams' ability to test in a production-like environment.

**What if I don't like the above restrictions?**

You can run your own version of the entire Patch demo website. Get yourself a server and follow the [Setup](#setup) instructions above, or convince an engineer near you to do it.

The public https://patchdemo.wmflabs.org/ website runs on an `m1.medium` instance at [Wikimedia Cloud VPS](https://wikitech.wikimedia.org/wiki/Portal:Cloud_VPS).

**Is it possible to add patches for extension not just core? And skins?**

Yes, patches for many extensions and skins are supported (mostly those included in MediaWiki releases, or enabled on all Wikimedia wikis), as well as Parsoid. Check out the list under "Choose extensions to enable" in the interface.

**What happens to a Patch demo wiki when the underlying patch is updated?**

Nothing. Once created, the wikis are never updated. New versions of the selected patches are not applied, and neither are patches merged into master. If you want to test a newer version of the patch, create a new wiki with it.


