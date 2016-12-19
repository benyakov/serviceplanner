<?php
system("wget http://jqueryui.com/resources/download/jquery-ui-1.12.1.zip");
system("wget http://code.jquery.com/jquery-3.1.1.min.js");
system("unzip jquery-ui-1.12.1.zip");

echo "If the above commands have worked, there will be a local copy of the jquery and jquery ui libraries in your installation. They will not be used unless the CDN urls are changed.";
