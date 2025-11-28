# Car Wash

## Prerequisites

### Windows Users: Install WSL (Windows Subsystem for Linux)

1. **Enable WSL**
   Open PowerShell as Administrator and run:
   ```powershell
   wsl --install
   ```

2. **Set WSL version to 2**
   ```powershell
   wsl --set-default-version 2
   ```

3. **Install Ubuntu 24.04**
   ```powershell
   wsl --install -d Ubuntu-24.04
   ```

   After installation, you'll be prompted to create a username and password for your Ubuntu environment.

4. **Verify installation**
   ```powershell
   wsl --list --verbose
   ```

   Ensure Ubuntu-24.04 is running on WSL version 2.

5. **Open WSL terminal**
   ```powershell
   wsl
   ```

   This will open your Ubuntu WSL terminal. Keep this terminal running for the following steps.

### Inside WSL: Install Docker Engine

Once you're inside your Ubuntu WSL environment, install Docker Engine:

1. **Add Docker's official GPG key**
   ```bash
   sudo apt-get update
   sudo apt-get install ca-certificates curl
   sudo install -m 0755 -d /etc/apt/keyrings
   sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
   sudo chmod a+r /etc/apt/keyrings/docker.asc
   ```

2. **Add the repository to Apt sources**
   ```bash
   echo \
     "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
     $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" | \
     sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
   sudo apt-get update
   ```

3. **Install Docker packages**
   ```bash
   sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
   ```

   When prompted, press `Y` then `Enter` to confirm the installation.

4. **Verify the installation**
   ```bash
   sudo docker run hello-world
   ```

   This command downloads a test image and runs it in a container. If successful, you'll see a confirmation message.

5. **Add user to docker group**
   ```bash
   sudo usermod -aG docker $USER
   ```

   After running this command, close all WSL terminals and reopen them for the changes to take effect.

6. **Reopen WSL terminal**
   ```powershell
   wsl
   ```

## Clone the Project

1. **Navigate to home directory**
   ```bash
   cd ~
   ```

2. **Clone the repository**

   If the folder already exists, delete it first:
   ```bash
   sudo rm -rf carwash
   ```

   Then clone the repository:
   ```bash
   git clone https://github.com/tekabu/carwash.git
   ```

3. **Navigate to the project directory**
   ```bash
   cd carwash
   ```

4. **Checkout to main branch**
   ```bash
   git checkout main
   ```

## Installation

1. **Set up environment files**

   Copy the `.env.example` files to `.env` for both admin and customer applications:
   ```bash
   cp src/admin/.env.example src/admin/.env
   cp src/customer/.env.example src/customer/.env
   ```

2. **Start the application**

   Run Docker Compose to start all services:
   ```bash
   docker compose up -d
   ```

3. **Verify containers are running**

   Check that all containers are running:
   ```bash
   docker compose ps -a
   ```

   You should see the following containers:
   - `car-wash-nginx` - Web server (ports 8011, 8012)
   - `car-wash-php` - PHP-FPM service
   - `car-wash-mysql` - MySQL database (port 8013)
   - `car-wash-phpmyadmin` - phpMyAdmin (port 8014)

4. **Access the PHP container**

   To run commands inside the PHP container:
   ```bash
   docker exec -it car-wash-php bash
   ```

5. **Setup Admin application**

   Inside the container, you'll see two folders: `admin` and `customer`. Set up admin first:
   ```bash
   ls
   cd admin
   ```

   Follow the instructions in the [admin README](src/admin/README.md) for installing dependencies. Make sure you are in the admin folder before running any commands.

6. **Setup Customer application**

   After completing the admin setup, set up the customer application:
   ```bash
   cd ../customer
   ```

   Follow the instructions in the [customer README](src/customer/README.md) for installing dependencies. Make sure you are in the customer folder before running any commands.

## Access URLs

Once the setup is complete, you can access the applications at:

- **Admin Application**: http://localhost:8011
- **Customer Application**: http://localhost:8012
- **phpMyAdmin**: http://localhost:8014

## Public Customer Top-up API

Third-party programs can create top-up requests by POSTing multipart data to the admin API:

```bash
curl -X POST http://localhost:8011/api/customer-top-ups \
  -F "customer_id=123" \
  -F "top_up_amount=500.00" \
  -F "status=Pending" \
  -F "remarks=Bank transfer reference XYZ" \
  -F "proof_of_payment=@/path/to/proof.jpg;type=image/jpeg"
```

Adjust the host, `customer_id`, and attachment path as needed; the endpoint returns the created record in JSON (201 Created).
