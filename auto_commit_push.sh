#!/bin/bash

# Configuration
USERNAME="youssef123855"
EMAIL="boumallala.youssef@esprit.tn"
REPO_URL="https://github.com/bouallegueMohamedSeji/synergygig-web.git"
BRANCH_NAME="post"

# 1. Update Git Config locally
echo "Configuring git credentials..."
git config user.name "$USERNAME"
git config user.email "$EMAIL"

# Calculate a random number of commits between 5 and 7
NUM_COMMITS=$(( ( RANDOM % 3 ) + 5 ))

echo "Will create $NUM_COMMITS commits with random timeouts (30-45s) between them..."

for (( i=1; i<=$NUM_COMMITS; i++ ))
do
    echo "Creating commit $i of $NUM_COMMITS..."
    
    # Simulate human work by modifying a dummy tracking file
    # (If you prefer to commit your actual changed files one by one, you can replace these lines)
    echo "Activity simulated at $(date)" >> timestamp_simulation.txt
    
    # Add and commit the file
    git add timestamp_simulation.txt
    git commit -m "Update codebase $(date +%H:%M:%S)"
    
    # If it's not the last commit, wait randomly between 30 and 45 seconds
    if [ $i -lt $NUM_COMMITS ]; then
        TIMEOUT=$(( ( RANDOM % 16 ) + 30 ))
        echo "Waiting for $TIMEOUT seconds to simulate human action..."
        sleep $TIMEOUT
    fi
done

# If there are any actual existing code changes you've made, let's commit them too in one final commit
if [ -n "$(git status --porcelain)" ]; then
    echo "Committing your actual application changes..."
    git add .
    git commit -m "feat: proceed with remaining implementations"
fi

# Push to the target repository and branch
echo "Pushing to $REPO_URL on branch $BRANCH_NAME..."
# Using HEAD:$BRANCH_NAME pushes the current active branch to the 'post' branch on the remote.
git push "$REPO_URL" HEAD:refs/heads/$BRANCH_NAME

echo "Operation completed successfully!"
