# d7-cpkg-builder

Builds new [cpkg](https://github.com/curator-wik/common-docs/blob/master/update_package_structure.md)
packages for consumption by the [Curator](https://github.com/curator-wik/curator) in-browser updater when it detects a new Drupal 7 release.

Needs consul on localhost. May be run on multiple nodes within the same consul
'datacenter' abstraction for redundancy.