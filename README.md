# Nucleus CMS UTF-8 Conversion

This attempts to convert your legacy [Nucleus](http://nucleuscms.org) database tables to UTF-8. Only the Nucleus tables in the database will be converted. There are a few Nucleus tables that do not have primary keys and thus cannot be converted by this program.

While I have tested this quite a bit on my own Nucleus install, it should be considered *experimental* and you should absolutely back up your entire Nucleus database before trying it.

## Installing and Using

1) Back up your database.

2) Upload the utf8/ directory to your nucleus/ directory.

3) You backed up your database, right?

4) In your browser, open the URL to the nucleus/utf8/ directory on your site. E.g. example.com/nucleus/utf8/

5) Click the link on that page to initiate the conversion process.

## Notes

Depending on the size of your database, the conversion process could take quite some time.

Many thanks to Sebastián Grignoli for his [forceutf8](https://github.com/neitanod/forceutf8) package, without which I probably never would have finished this.

