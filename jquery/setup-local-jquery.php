<?php
if (! file_exists("jquery-ui-1.12.1.zip"))
    system("wget http://jqueryui.com/resources/download/jquery-ui-1.12.1.zip");
if (! file_exists("jquery-3.1.1.min.js"))
    system("wget http://code.jquery.com/jquery-3.1.1.min.js");
if (! file_exists("jquery-ui-1.12.1"))
    system("unzip jquery-ui-1.12.1.zip");

$fh = fopen("locations.json", "w");
fwrite(json_encode(["jquery" => "jquery/jquery-3.1.1.min.js",
    "ui" => "jquery/jquery-ui-1.12.1/jquery-ui.min.js",
    "style" => "jquery/jquery-ui-1.12.1/jquery-ui.css"
]));
fclose($fh);
//fwrite(json_encode(["jquery" => "https://code.jquery.com/jquery-3.1.1.min.js",
//    "ui" => "https://code.jquery.com/ui/1.12.1/jquery-ui.min.js",
//    "style" => "https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"
//]));

?>
<p>If the above commands have worked, there will be a local copy of the jquery
and jquery ui libraries in your installation. The system should now use those
instead of the CDN. You can switch back to the CDN by loading
setup-cdn-jquery.php instead.</p>
