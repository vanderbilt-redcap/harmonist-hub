# IeDEA-Harmonist Build & Deploy Steps 

<h2>1. Recover Project Data</h2>

To recover the project’s data you first have to go to GitHub and download the project, which is under the name *harmonist-hub*.

1. Go to Github: [https://github.com/vanderbilt-redcap/harmonist-hub](https://github.com/vanderbilt-redcap/harmonist-hub)  
2. Click on the  \<\> Code  button and copy the SSH url.  
   <img src="/docs/images/image1.png" alt="This picture shows Github's Harmonist main page">
3. On the server, in the modules folder, do **git clone \<url\> \<name\>** to copy the project.

<h2>2. Activate and Configure the Module on a Project</h2>

Once the project that will host Harmonist Hub is created in REDCap, we need to activate the module.

1. First, an administrator has to activate the module on the Control Center if it has not been activated previously.   
   1. Control Center → External Modules → Manage → Enable a module → Enable  
2. Afterwards, activate the module on the project.  
   1. External Modules → Manage → Enable a Module  
3. Once the Module is active click on Configure.

<img src="/docs/images/image2.png" alt="This picture shows the External Modules Manager's Page">
<img src="/docs/images/image3.png" alt="This picture shows Harmonist's External Modules Managers Configuration Page">

4. In the configuration window enter the mandatory fields.  
   1. Hub Name: The name the hub and projects will have. This will be the main name added to all projects. It is recommended to not add a very long name.  
   2. Hub Profile: This field is mandatory but any value works.

<h2>3. First Time Install</h2>

If this is the first time activating the module, after configuring it, you need to install all the projects that will make the module work.

1. On the External Modules section click on the new link that will appear. The First time you see the link, it will be under Harmonist Hub, that will change later to your Hub’s name.  

<img src="/docs/images/image4.png" alt="This picture shows Harmonist's External Module Link">

2. A message will display prompting the user to click the button to create and install the necessary projects to use the external module.

<img src="/docs/images/image5.png" alt="This picture shows Harmonist's first time installation notice">

3. Click and install the projects and you are ready to start adding data to them.  
4. It is recommended to start with the “Settings” project.

<h2>4. Data Hub</h2>

If the project also has a Data Hub, there are extra steps that need to be followed.

1. In the server:  
   1. If it does not exists, create a new folder “Harmonist-Hub” in the PROD server in /app001/credentials/   
   2. Make sure these directories and files are readable by the web server user.  
   3. Create the file  PID\_down\_crypt.php  and add the keys with their values:  
      1. $secret\_key  
      2. $secret\_iv  
   4. Create the file PID\_aws\_s3.php  and add the keys with their values:  
      1. $aws\_key  
      2. $aws\_secret  
   5. Note: PID is the number of the project where we installed the Harmonist Hub External Module.  
   6. Note: the keys need to be paired with the *Harmonist Data Toolkit* as they will both use the same to connect together.  
   7. In production we must remember to do this on all servers:  
      1. ori1007lp   
      2. ori1008lp   
      3. Ori1023lp
