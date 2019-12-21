<form action="new.php" method="POST">

<p>Start with version:</p>
<select name="branch">
<?php
require_once "includes.php";

$gitcmd = "git --git-dir=" . __DIR__ . "/repositories/mediawiki/core/.git";
// basically `git branch -r`, but without the silly parts
$branches = explode( "\n", shell_exec( "$gitcmd for-each-ref refs/remotes/origin/ --format='%(refname:short)'" ) );

$branches = array_filter( $branches, function ( $branch ) {
	return preg_match( '/^origin\/(master|wmf|REL)/', $branch );
} );
natcasesort( $branches );

foreach ( $branches as $branch ) {
	echo "<option>" . htmlspecialchars( $branch ) . "</option>\n";
}

?>
</select>

<p>Then, apply patches: <small>(Gerrit changeset number or Change-Id, one per line)</small></p>
<textarea name="patches"></textarea>

<br>
<button type="submit">Create demo</button>

</form>
