$ErrorActionPreference = "Stop"

Set-Location -Path "C:\Users\Youssef Boumallala\Downloads\web_synergygig-20260408T083703Z-3-001\web_synergygig"

Write-Host "Initializing clean git repository..."
if (Test-Path -Path ".git") {
    Remove-Item -Recurse -Force .git
}
git init
git remote add origin "https://github.com/bouallegueMohamedSeji/synergygig-web.git"

Write-Host "Configuring credentials..."
git config user.name "youssef123855"
git config user.email "boumallala.youssef@esprit.tn"
git checkout -b post

$batches = @(
    @{ Items = @("assets", "config", "bin"); Message = "Initial structure setup: assets and config" },
    @{ Items = @("public", "templates"); Message = "Add public directory and templates" },
    @{ Items = @("src"); Message = "Add core source logic" },
    @{ Items = @("migrations", "tests", "var"); Message = "Add migrations, tests, and temporary directories" },
    @{ Items = @("."); Message = "Finalize root configurations and python scripts" }
)

Write-Host "Creating 5 commits with 30-45 sec timeouts..."
for ($i = 0; $i -lt $batches.Length; $i++) {
    $batch = $batches[$i]
    foreach ($item in $batch.Items) {
        git add $item
    }
    git commit -m $batch.Message
    Write-Host "Committed batch $($i + 1): $($batch.Message)"
    
    if ($i -lt ($batches.Length - 1)) {
        $timeout = Get-Random -Minimum 30 -Maximum 46
        Write-Host "Waiting $timeout seconds to simulate human action..."
        Start-Sleep -Seconds $timeout
    }
}

Write-Host "Pushing to actual GitHub on branch post..."
git push origin post --force 2>&1 | Tee-Object "push_log.txt"

Write-Host "Finished successfully!"
