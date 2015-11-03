Beeminder API
=============

A PHP interface to the Beeminder API. Handles everything in v1, although it
still needs much better error handling. 


Official Library
----------------
Make sure you check out the actual [official library](https://github.com/beeminder/beeminder-php-api)


Usage
-----

To make the example work, set
- BeeminderSession::REDIRECT if you use OAuth (in beeminderapi.php)
- token when you set up connection (in example.php)
- username and goal name in API requests (in example.php)


Credits
-------

- Simple design based on the [Python library](https://github.com/mattjoyce/beeminderpy/blob/master/beeminderpy.py)

