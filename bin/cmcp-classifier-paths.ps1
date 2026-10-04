$ErrorActionPreference = 'Stop'
php -d xdebug.mode=coverage bin/cmcp-classifier-paths.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
