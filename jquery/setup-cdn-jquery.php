<?php
$fh = fopen("locations.json", "w");
fwrite(json_encode(["jquery" => "https://code.jquery.com/jquery-3.1.1.min.js",
    "ui" => "https://code.jquery.com/ui/1.12.1/jquery-ui.min.js",
    "style" => "https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"
]));
fclose($fh);

?>
<p>The system should now use the CDN instead of local copies of the jquery and jquery-ui libraries. You can switch back to the local copies by loading
setup-local-jquery.php instead.</p>
