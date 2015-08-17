<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

require_once('MinecraftPing.php');
require_once('MinecraftPingException.php');

use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

$timer = microtime(true);

$info = false;
$query = null;

try
{
    $query = new MinecraftPing('localhost', 25565, 1);
    $info = $query->query();

    if ($info === false)
    {
        $query->close();
        $query->connect();

        $info = $query->queryOldPre17();
    }

    if ($info === false)
    {
        $query->close();
        $query->connect();

        $info = $query->queryBungeeCord();
    }
}
catch(MinecraftPingException $e)
{
	$exception = $e;
}

if ($query !== null)
{
	$query->close();
}

$timer = number_format(microtime(true) - $timer, 4, '.', '');
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Minecraft Ping PHP Class</title>

		<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
		<style type="text/css">
		.jumbotron
		{
			margin-top: 30px;
			border-radius: 0;
		}

		.table thead th
		{
			background-color: #428BCA;
			border-color: #428BCA !important;
			color: #FFF;
		}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="jumbotron">
				<h1>Minecraft Ping PHP Class</h1>

				<p>This class was created to query Minecraft servers. It works starting from Minecraft 1.0.</p>
			</div>
			<?php
			if (isset($exception))
			{
				?>
				<div class="panel panel-primary">
					<div class="panel-heading">
						<?php echo htmlspecialchars($exception->getMessage()); ?>
					</div>
					<p>
						<?php echo nl2br($exception->getTraceAsString(), false); ?>
					</p>
				</div>
				<?php
			}
			else
			{
				?>
				<div class="row">
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th colspan="2">
									Server Info <em>(queried in <?php echo $timer; ?>s)</em>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							if ($info !== false)
							{
								foreach($info as $infoKey => $infoValue)
								{
									?>
									<tr>
										<td>
											<?php echo htmlspecialchars($infoKey); ?>
										</td>
										<td>
											<?php
											if ($infoKey === 'favicon')
											{
												echo '<img width="64" height="64" src="' . Str_Replace( "\n", "", $infoValue ) . '">';
											}
											elseif (is_array($infoValue))
											{
												echo "<pre>";
												print_r($infoValue);
												echo "</pre>";
											}
											else
											{
												echo htmlspecialchars($infoValue);
											}
											?>
										</td>
									</tr>
									<?php
								}
							}
							else
							{
								?>
								<tr>
									<td colspan="2">No information received</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
			<?php
			}
			?>
		</div>
	</body>
</html>