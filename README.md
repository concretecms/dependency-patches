[![Tests](https://github.com/mlocati/concretecms-dependency-patches/actions/workflows/tests.yml/badge.svg)](https://github.com/mlocati/concretecms-dependency-patches/actions/workflows/tests.yml)
# Dependency patches for concrete5 and Concrete CMS

concrete5 v8 and Concrete CMS v9+ use a lot of third party libraries, installed via Composer.

Internal changes in newer PHP versions require to upgrade some of those composer packages, but some of them are no more compatible with the PHP versions we support, or they haven't been fixed yet.

This `dependency-patches` project contains those required patches, so that concrete5 and Concrete CMS can still use them.


## How to use

The official releases of concrete5 and Concrete CMS that can be downloaded from https://www.concretecms.org/download already contain the patches included in `dependency-patches`.

If you use a composer-based concrete5/Concrete CMS installation you need to add these lines to your `composer.json` file:

- in the `require` section (it it's not already included in the `composer.json` file that comes with concrete5/Concrete CMS):
  ```json
  "concretecms/dependency-patches": "^1",
  ```
- in the `extra` section:
  ```json
  "allow-subpatches": [
      "concretecms/dependency-patches"
  ],
  ```


## How to add a new patch

If you want to patch a composer package named `<vendor>/<package>` at version `1.2.3`, you should:

1. create the `.patch` file:
    1. in the Concrete root directory, run `composer reinstall <vendor>/<package> --prefer-source` (requires composer 2.1+) to have a git repository
    2. run `git checkout -b my-patch <tag>` inside the package directory (where `<tag>` is the tag corresponsing to the installed package version)
    3. edit the required files
    4. create a commit with the changes, by running `git commit -am "My wonderful patch"`
    5. create a patch file by running `git format-patch --no-stat -1`
    6. edit that patch by removing useless lines, like:
        - the initial `From <sha1> <date>`
        - the git-specific lines (they start with `diff --git ...` and `index sha1..sha1`
        - the closing comments, if any (the `--` line at the end of the file and any other lines after it)
    7. move the .patch file to the `<vendor>/<package>` directory in the dependency-patches repository
2. add a `<vendor>/<package>:1.2.3` key to the `extra`.`patches` section of the `composer.json` file of this project.
   For example:
   ```json
   "<vendor>/<package>:1.2.7": {
       "Description of the patch": "<vendor>/<package>/name-of-the-patch-file.patch"
   },
   ```
3. to test the patch locally, you can edit the `composer.json` file of your concrete5/Concrete CMS installation, adding:
   - In the `require` section:
     ```json
     "concretecms/dependency-patches": "dev-master"
     ```
   - In the `repositories` section:
     ```json
     {
         "type": "path",
         "url": "relative/or/absolute/path/to/your-local/dependency-patches"
     }
     ```
     PS: on Windows, you can use forward slashes (`/`) instead of back-slashes (`\`) as the directory separator.
