# User UI Flow Retries

## Strategy
Whenever an error occurs which can be an API error, timeout or network failure, the application notifies the user about the details of the error and provides necessary information or directions to do the next thing (retry). 

The below describes the major user flows in the application and how multiple UI scenarios are handled when systems go down.

### Jobs Flow
This flow enables user to see the list of jobs scheduled along with the latest job details, user can click on one of the jobs in the jobs list screen and view the details.

#### Jobs List - Not Authorized
When an unauthorized user tries to access jobs list screen, user will be shown a 403 error screen and a link to navigate to login (auth/tokens) to retry with valid credentials.

#### Jobs List - No Jobs
When the user tries to access jobs list and no jobs are available, user will be shown the steps to configure pipelines and how to start a job.

#### Jobs List - Start Job Failure
If an error occurs when user tries to start a job, he/she will be shown a flash message about the details of the error and can start a job again using 'Start Job' button.

#### Job Details - Not Authorized
When an unauthorized user tries to access job details screen, user will be shown a 403 error screen and a link to navigate to jobs list screen (/jobs) to retry by clicking a valid job id.
  
#### Job Details - Invalid job Id
When user tries to access a job with an invalid job Id , user will be shown a 404 error screen and a link to navigate to jobs list screen (/jobs).  

### Application Info flow
This flow enables user to see the important application information and configure the application to connect to GitHub/Acquia-git.
  
#### View Application Info - Unable to retrieve information
If an error occurs while accessing the application info, user will be shown a flash message with the error. He/she can view the detailed information of the error by clicking 'More Details' and retry again by refreshing the page or visiting the application info page again.

#### Configure (GitHub/ Acquia) - Error connecting to VCS
If an error occurs while configuring the application to connect GitHub/Acquia-git, user will be shown an alert with the error details. User can retry by clicking 'Configure' button.

#### Select GitHub Repository - Unable to fetch repos
If an error occurs while fetching the GitHub repositories, user will be shown an alert with the error details. User can retry by clicking the 'Select Github Repository' button.

#### Configure Pipelines - Start Job Failure
If an error occurs when user tries to start a job, he/she will be shown a flash message about the details of the error and can start a job again using 'Start Job' button.
