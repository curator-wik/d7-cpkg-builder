# d7-cpkg-builder

Builds new [cpkg](https://github.com/curator-wik/common-docs/blob/master/update_package_structure.md)
packages for consumption by the [Curator](https://github.com/curator-wik/curator) in-browser updater when it detects a new Drupal 7 release.

Needs
 * consul agent on localhost.
 * git in your `PATH`
 * A directory called `repo` in the working directory that is initialized as
   a git repository with a remote that the user can push to non-interactively.
   Release .cpkg files are force-pushed here.
 
May be run on multiple nodes within the same consul 'datacenter' abstraction for redundancy;
new releases are only built once.

Run `bin/d7-cpkg-builder.php` at a reasonable frequency.