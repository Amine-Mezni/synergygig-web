$USERNAME = "youssef123855"
$EMAIL = "boumallala.youssef@esprit.tn"
$REPO_URL = "https://github.com/bouallegueMohamedSeji/synergygig-web.git"
$BRANCH_NAME = "post"

Write-Host "Configuring git credentials..."
git config user.name $USERNAME
git config user.email $EMAIL

$NUM_COMMITS = Get-Random -Minimum 5 -Maximum 8
Write-Host "Will create $NUM_COMMITS commits with random timeouts (30-45s) between them..."

for ($i = 1; $i -le $NUM_COMMITS; $i++) {
    Write-Host "Creating commit $i of $NUM_COMMITS..."
    $date = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path timestamp_simulation.txt -Value "Activity simulated at $date"
    git add timestamp_simulation.txt
    git commit -m "refactor: auto update $date"
    
    if ($i -lt $NUM_COMMITS) {
        $TIMEOUT = Get-Random -Minimum 30 -Maximum 46
        Write-Host "Waiting for $TIMEOUT seconds to simulate human action..."
        Start-Sleep -Seconds $TIMEOUT
    }
}

$GitStatus = git status --porcelain
if (![string]::IsNullOrWhiteSpace($GitStatus)) {
    Write-Host "Committing your actual application changes..."
    git add .
    git commit -m "feat: final push elements"
}

Write-Host "Pushing to $REPO_URL on branch $BRANCH_NAME..."
git push $REPO_URL "HEAD:refs/heads/$BRANCH_NAME"

Write-Host "Operation completed successfully!"
