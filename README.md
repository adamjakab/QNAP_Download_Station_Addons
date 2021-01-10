QNAP DOWNLOAD STATION ADDONS
============================

Status: Experimental 


Addons:
- 1337x
- Il Corsaro Nero


How to use
----------
Manually upload the content of the "addons" folers to the corresponding folder on your NAS (something like `/share/MD0_DATA/.qpkg/DSv3/usr/sbin/addons/`). Open your Download Station app on your NAS, go to Settings, and click the refresh button in the "Ass-on" tab. It will load the newly uploaded add-ons. Make sure you ebnable them if you plan to use them.

Manual run
----------
You can run these addons from your terminal from your nas. Follow the addon developer guide to identify where your addons are located. Go to the `sbin` folder and run:

    ./ds-addon -s [addon name] [search string] [result limit]

So, for example you could run:

    ./ds-addon -s 1337x ubuntu 3


Roadmap
-------
None.


Documentation
--------------
http://download.qnap.com/dev/download-station-addon-developers-guide_v4.pdf

