$files = @(
  @{local="src\Service\N8nWebhookService.php"; remote="src/Service/N8nWebhookService.php"},
  @{local="src\Service\AIService.php"; remote="src/Service/AIService.php"},
  @{local="src\Service\NotificationService.php"; remote="src/Service/NotificationService.php"},
  @{local="src\Controller\InterviewController.php"; remote="src/Controller/InterviewController.php"},
  @{local="src\Controller\ContractController.php"; remote="src/Controller/ContractController.php"},
  @{local="templates\contract\show.html.twig"; remote="templates/contract/show.html.twig"},
  @{local="config\services.yaml"; remote="config/services.yaml"}
)
$base = "C:\Users\seji\Desktop\java\SynergyGig\web_synergygig"
$srv  = "root@64.23.239.27"

foreach ($f in $files) {
  $name = [System.IO.Path]::GetFileName($f.local)
  Write-Host ">> $($f.remote)" -ForegroundColor Cyan
  scp "$base\$($f.local)" "${srv}:/tmp/${name}"
  ssh $srv "docker cp /tmp/${name} synergygig-web:/srv/web_synergygig/$($f.remote) && rm /tmp/${name} && echo 'OK: $($f.remote)'"
}

Write-Host "`nClearing prod cache..." -ForegroundColor Yellow
ssh $srv "docker exec synergygig-web sh -c 'rm -rf /srv/web_synergygig/var/cache/prod && php /srv/web_synergygig/bin/console cache:warmup --env=prod 2>&1 | tail -5'"

Write-Host "`nDone!" -ForegroundColor Green
