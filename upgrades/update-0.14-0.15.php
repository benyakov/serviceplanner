<?
require('../init.php');
if (! $auth) {
    header("location: ../index.php");
    exit(0);
}
$dbh->exec("CREATE TABLE `users` (
  `uid` smallint NOT NULL auto_increment,
  `username` char(15) NOT NULL,
  `password` char(32) NOT NULL,
  `fname` char(20) NOT NULL,
  `lname` char(30) NOT NULL,
  `email` char(40) default NULL,
  `resetkey` text default NULL,
  `resetexpiry` datetime default NULL,
  PRIMARY KEY (`uid`)
) TYPE=InnoDB DEFAULT CHARSET=utf8;")
?>
