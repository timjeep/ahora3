#!/bin/bash
sudo chown -R apache:apache /var/www/html/ahora3
sudo chmod -R g+w /var/www/html/ahora3
cd /var/www/html/ahora3/
if [ -s storage/logs/after-install.log ]; then
    sudo rm -f storage/logs/after-install.log
fi
if [ -s .env ]; then
    sudo rm -f .env
fi

# Get the SSM parameter names from environment variables or use defaults
SSM_PARAMETER_CORE=${SSM_PARAMETER_CORE:-"/ahora/prod/env-core"}
SSM_PARAMETER_SECRETS=${SSM_PARAMETER_SECRETS:-"/ahora/prod/env-secrets"}
AWS_REGION=${AWS_REGION:-"ca-central-1"}

# Create .env file by merging two AWS Systems Manager Parameter Store parameters
echo "Retrieving .env files from AWS SSM Parameter Store" | sudo tee -a storage/logs/after-install.log
echo "  - Core config: $SSM_PARAMETER_CORE" | sudo tee -a storage/logs/after-install.log
echo "  - Secrets: $SSM_PARAMETER_SECRETS" | sudo tee -a storage/logs/after-install.log

# Fetch core configuration (non-sensitive settings)
# Use /tmp for tmp files: ec2-user can write there; app dir is apache-owned after chown above
aws ssm get-parameter \
    --name "$SSM_PARAMETER_CORE" \
    --with-decryption \
    --region "$AWS_REGION" \
    --query Parameter.Value \
    --output text | tr -d $'\r' > /tmp/.env.tmp1 2>&1

CORE_STATUS=$?

# Fetch secrets (sensitive credentials and API keys)
aws ssm get-parameter \
    --name "$SSM_PARAMETER_SECRETS" \
    --with-decryption \
    --region "$AWS_REGION" \
    --query Parameter.Value \
    --output text | tr -d $'\r' > /tmp/.env.tmp2 2>&1

SECRETS_STATUS=$?

# Merge both files into .env
if [ $CORE_STATUS -eq 0 ] && [ $SECRETS_STATUS -eq 0 ]; then
    cat /tmp/.env.tmp1 | sudo tee /var/www/html/ahora3/.env > /dev/null
    echo "" | sudo tee -a /var/www/html/ahora3/.env > /dev/null
    cat /tmp/.env.tmp2 | sudo tee -a /var/www/html/ahora3/.env > /dev/null
    rm -f /tmp/.env.tmp1 /tmp/.env.tmp2
    echo "Successfully merged .env files from AWS SSM Parameter Store" | sudo tee -a storage/logs/after-install.log
elif [ $CORE_STATUS -eq 0 ]; then
    sudo cp /tmp/.env.tmp1 /var/www/html/ahora3/.env
    rm -f /tmp/.env.tmp1 /tmp/.env.tmp2
    echo "WARNING: Only retrieved core config, secrets not found" | sudo tee -a storage/logs/after-install.log
else
    rm -f /tmp/.env.tmp1 /tmp/.env.tmp2
    echo "Failed to retrieve .env files from AWS SSM Parameter Store" | sudo tee -a storage/logs/after-install.log
    if [ -s /home/ec2-user/ahorasite.env ]; then
        sudo cp /home/ec2-user/ahorasite.env /var/www/html/ahora3/.env
        echo "Successfully copied .env file from local machine as fallback" | sudo tee -a storage/logs/after-install.log
    else
        echo "ERROR: .env file not found on local machine" | sudo tee -a storage/logs/after-install.log
        exit 1
    fi
fi

# Verify the final file was created and has content
if [ -s /var/www/html/ahora3/.env ]; then
    set -a
    source /var/www/html/ahora3/.env
    set +a
    echo "Final .env file created successfully ($(wc -l < /var/www/html/ahora3/.env) lines)" | sudo tee -a storage/logs/after-install.log
else
    echo "ERROR: Final .env file is empty or missing!" | sudo tee -a storage/logs/after-install.log
    exit 1
fi
# Retrieve GitHub OAuth token from SSM Parameter Store
GITHUB_TOKEN=$(aws ssm get-parameter \
    --name "github-oauth" \
    --with-decryption \
    --region "$AWS_REGION" \
    --query Parameter.Value \
    --output text 2>&1)
if [ $? -eq 0 ] && [ -n "$GITHUB_TOKEN" ]; then
    export COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"$GITHUB_TOKEN\"}}"
    echo "Successfully retrieved GitHub OAuth token from SSM" | sudo tee -a storage/logs/after-install.log
else
    echo "ERROR: Failed to retrieve GitHub OAuth token from SSM" | sudo tee -a storage/logs/after-install.log
    exit 1
fi
sudo composer -n install 2>&1 | sudo tee -a storage/logs/after-install.log
sudo chown -R apache:apache /var/www/html/ahora3 2>&1 | sudo tee -a storage/logs/after-install.log
sudo chmod -R g+w /var/www/html/ahora3 2>&1 | sudo tee -a storage/logs/after-install.log
sudo npm install 2>&1 | sudo tee -a storage/logs/after-install.log
sudo npm rebuild 2>&1 | sudo tee -a storage/logs/after-install.log
sudo npm run build 2>&1 | sudo tee -a storage/logs/after-install.log
sudo chown -R apache:apache /var/www/html/ahora3 2>&1 | sudo tee -a storage/logs/after-install.log
sudo chmod -R g+w /var/www/html/ahora3 2>&1 | sudo tee -a storage/logs/after-install.log
sudo php artisan --force migrate 2>&1 | sudo tee -a storage/logs/after-install.log
sudo php artisan cache:clear 2>&1 | sudo tee -a storage/logs/after-install.log
sudo php artisan config:cache 2>&1 | sudo tee -a storage/logs/after-install.log
sudo php artisan view:cache 2>&1 | sudo tee -a storage/logs/after-install.log
sudo php artisan route:cache 2>&1 | sudo tee -a storage/logs/after-install.log
sudo php artisan app:after-update 2>&1 | sudo tee -a storage/logs/after-install.log
curl -X POST -H 'Content-type: application/json' --data '{"text":"Ahora Site 3 Updated!"}' "$LOG_SLACK_WEBHOOK_URL"
mail -s "Ahora Site 3 Update Log" -a storage/logs/after-install.log tmurphy@tech2tel.com < /dev/null
# if this generated logs for the first time of the day it will be owned by root, so we need to change the ownership to apache
sudo chown -R apache:apache /var/www/html/ahora3/storage/logs