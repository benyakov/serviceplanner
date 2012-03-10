<?
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Planning Records")?>
<body>
<script type="text/javascript">
    auth = "<?=authId()?>";
    $(document).ready(function() {
        setupLogin("<?=authId()?>");
    });
</script>
    <header>
    <div id="login"><?=getLoginForm()?></div>
    <?showMessage();?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <div id="goto-now"><a href="#now">Jump to This Week</a></div>
    <?
    include("records-table.php");
    ?>
    </div>
</body>
</html>
