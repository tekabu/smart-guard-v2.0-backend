# Installing Ubuntu 24.04 in WSL and Docker Engine

This guide will walk you through installing Ubuntu 24.04 in Windows Subsystem for Linux (WSL) and setting up Docker Engine.

## Prerequisites

- Windows 10 version 2004 or higher (Build 19041 and above)
- Administrator privileges on your Windows machine

## Step 1: Install WSL

1. Open PowerShell as Administrator
2. Run the following command to install WSL:

```powershell
wsl --install
```

This command will enable the required Windows features, download and install the latest Linux kernel, and set WSL 2 as your default.

3. Restart your computer when prompted.

## Step 2: Install Ubuntu 24.04

1. After restarting, open Microsoft Store from the Start Menu
2. Search for "Ubuntu 24.04 LTS"
3. Click on "Get" or "Install" to download and install Ubuntu 24.04
4. Once installed, launch Ubuntu from the Start Menu or by running `ubuntu2404` in PowerShell/CMD
5. Follow the on-screen instructions to create your username and password

## Step 3: Update Ubuntu System

1. Open your Ubuntu terminal
2. Update the package list and upgrade installed packages:

```bash
sudo apt update
sudo apt upgrade -y
```

## Step 4: Install Docker Engine

1. Add Docker's official GPG key:

```bash
sudo apt update
sudo apt install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

2. Add the repository to Apt sources:

```bash
sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Signed-By: /etc/apt/keyrings/docker.asc
EOF
```

3. Update the package list and install Docker Engine:

```bash
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin -y
```

## Step 5: Manage Docker as a Non-Root User (Optional but Recommended)

1. Create the docker group if it doesn't exist:

```bash
sudo groupadd docker
```

2. Add your user to the docker group:

```bash
sudo usermod -aG docker $USER
```

3. Activate the changes to groups:

```bash
newgrp docker
```

## Step 6: Test Docker Installation

1. Test your Docker installation by running the hello-world container:

```bash
sudo docker run hello-world
```

If you've completed Step 5 and added your user to the docker group, you can run:

```bash
docker run hello-world
```

2. If successful, you'll see a message indicating that Docker is installed and running correctly.

## Step 7: Start Docker Service on Boot (Optional)

To ensure Docker starts automatically when WSL starts:

```bash
sudo systemctl enable docker
```

## Troubleshooting

- If Docker doesn't start, you may need to start the service manually:

```bash
sudo systemctl start docker
```

- If you encounter permission issues, make sure your user is in the docker group or use `sudo` before Docker commands.

- For more detailed information, refer to the [official Docker documentation](https://docs.docker.com/engine/install/ubuntu/).

## Additional Resources

- [WSL Documentation](https://docs.microsoft.com/en-us/windows/wsl/)
- [Docker Documentation](https://docs.docker.com/)
- [Ubuntu Documentation](https://help.ubuntu.com/)