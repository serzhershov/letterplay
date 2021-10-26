# letterplay project for some practice in symfony, and a test assigment at that
single command application based on symfony console module, with some basic tests

basic command executed with:
$ php application.php -i "public/valid-input.txt" --format "least-repeating" -S -P -L
--input /-i parameter must be provided and point to a file with corresponding content (lower case alphabet ASCII letters, punctuations and symbols only)
available formats: non-repeating, least-repeating, or most-repeating
available flags: -S (for symbol), -P (for punctuation) and -L (for letter)
- must note that I was not totally sure with some symbol qualification to symbols or punctuation, so this is most fiddicky


tests executed with:
$ ./vendor/bin/phpunit tests --testdox
