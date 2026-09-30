# How to add a new patch

If you want to patch a composer package named `<vendor>/<package>` at version `1.2.3`, you should:

1. create the `.patch` file:
    1. in the Concrete root directory, run `composer reinstall <vendor>/<package> --prefer-source` (requires composer 2.1+) to have a git repository
    2. run `git checkout -b my-patch <tag>` inside the package directory (where `<tag>` is the tag corresponding to the installed package version)
    3. edit the required files
    4. create a commit with the changes, by running `git commit -am "My wonderful patch"`
    5. create a patch file by running `git format-patch --no-stat -1`
    6. edit that patch by removing useless lines, like:
        - the initial `From <sha1> <date>`
        - the git-specific lines (they start with `diff --git ...` and `index sha1..sha1`)
        - the closing comments, if any (the `--` line at the end of the file and any other lines after it)
    7. if the patch is a backport of an upstream commit, add to the description of the patch (after the `Subject:` line) a link to that commit and a `Co-authored-by: Name <email>` line with its author
    8. move the .patch file to the `<vendor>/<package>` directory in the dependency-patches repository.
       Name it after what it fixes (for security fixes: `cve-<year>-<number>-<short-description>.patch`).
       Don't change its line endings: they must be the same as in the patched files (that's why `.gitattributes` disables their normalization).
2. add a `<vendor>/<package>:1.2.3` key to the `extra`.`patches` section of the `composer.json` file of this project.
   The version must be the exact version of the package (the tests install it and apply the patches to it).
   For example:
   ```json
   "<vendor>/<package>:1.2.3": {
       "Description of the patch": "<vendor>/<package>/name-of-the-patch-file.patch"
   },
   ```
3. if the patch fixes a security advisory, add its ID to the `audit`.`ignore` example of the [README.md](README.md) file, and list the patched version in its "Security advisories" section.
4. check that all the patches can be applied, by running `php ./.github/workflows/tests.php` (it requires `composer` and GNU `patch`; on Windows, run it from Git Bash)
5. to test the patch locally, you can edit the `composer.json` file of your concrete5/Concrete CMS installation, adding:
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


# How to release a new version

1. once the patch is merged, tag a new version of `dependency-patches`
2. in Concrete CMS, run `composer update concretecms/dependency-patches` to update the `composer.lock` file
3. if Concrete CMS needs the new patch (for example because it ignores a security advisory fixed by it), raise the required version of `concretecms/dependency-patches` in its `concrete/composer.json` file
