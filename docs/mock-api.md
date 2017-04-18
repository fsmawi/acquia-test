# Mock API Development Server

## Definition
1. An AWS Beanstalk hosted custom node.js container and server.
2. A dedicated repo for maintenance and support separate from the UI repository.
3. Limited AWS credentials for pipelines to deploy to beanstalk with the repository.

## Rationale
1. Dedicated Mock API
2. Built on Node
3. Deployed on Beanstalk

### Why a dedicated mock API server?
To truly decouple development the backend and front end teams, a mock API allows both teams to agree on integration, then develop in parallel, increasing both teams velocity.  Having a mock API also allows the UI team to recreate repeatable scenarios with very little technical instrumentation to "test bedding".

### Why a node server?
Originally, a node server for the mock API was used ([raghunat/merver](https://github.com/raghunat/merver)), and is still in use during local development.  To use ODE/CDE's effectively and test them using the same mocks, a PHP version was created, and has allowed the UX team to continue to use the mock API.  With the move to a completely cloudfront hosted application, there was no need for the PHP/Acquia Cloud based environment, and would then need a separate API to continue this feature.  Node was chosen as the tool chain from the UI solely uses node for all other processes (CI, CD, unit tests, integration tests, end to end tests, release automation, etc).  This also allows the same development mock api code to be maintained and used throughout the development cycle and environments.

### Why AWS Beanstalk?
Beanstalk will allow the Mock API to deploy with high velocity, and maintain high availability for any development needs with minimal infrastructure cost or complexity. Beanstalk uses custom configuration files in the repo for deployment, paired with environment variables to provide an easily reproduced API service host.

[From their feature brief](https://aws.amazon.com/elasticbeanstalk/):

- *Elastic Beanstalk automatically handles the deployment details of capacity provisioning, load balancing, auto-scaling, and application health monitoring. Within minutes, your application will be ready to use without any infrastructure or resource configuration work on your part.*
- *Elastic Beanstalk provisions and operates the infrastructure and manages the application stack (platform) for you, so you don't have to spend the time or develop the expertise.*
- *Elastic Beanstalk automatically scales your application up and down based on your application's specific need using easily adjustable Auto Scaling settings.*
- *You have the freedom to select the AWS resources, such as Amazon EC2 instance type, that are optimal for your application.*

#### Other References
- [AWS Guide](http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/create_deploy_nodejs.html)
- [AWS EB Express Example](http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/create_deploy_nodejs_express.html)

## Architecture
A connect based API that configures API responses from predefined YAML files assigned by sessions and headers. This API is protected through passport and CORS restrictions (whitelisted by environment variables during deployment).

### Core Features
- YAML defined API response stories that allow a flow of API requests and responses
- Cookie and Session based support for multi call response flows
- YAML defined websocket stories that allow a mock websocket integration
- API authorization and authentication based on a "local" strategy
- CORS restrictions to applicable domains

### Core Packages
- [Express](https://expressjs.com/) - Main ReST API framework
- [WS](https://github.com/websockets/ws) - Web standard compliant websocket framework
- [Passport](http://passportjs.org/) - Local strategy authentication identity provider
- [Sequelize](http://docs.sequelizejs.com/en/v3/) - Data ORM for Sqlite/MySQL
- [Express Sessions](https://github.com/expressjs/session) - Session parser middleware
- [Express Cookies](https://github.com/expressjs/cookie-parser) - Cookie parser middleware
- [Express CORS](https://github.com/expressjs/cors) - CORS defintion middleware
- [Body Parser](https://github.com/expressjs/body-parser) - Content parser
- [JS YAML](https://github.com/nodeca/js-yaml) - YAML parser
- [PM2](http://pm2.keymetrics.io/) - Process clustering and production runtime client
- [Dotenv](https://github.com/motdotla/dotenv) - Environment bootstrapping

## Deployment Strategy
1. All infrastrcuture is managed by the `.elasticbeanstalk` directory for AWS beanstalk integration
2. Deployments are done using the [EB CLI](http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/eb-cli3.html) (v3) with the conventional `eb deploy` command
3. Production environment credentials are stored by environment variables of the engineer, as a `.env` file in the root of the reposiotry.

### Credentials
- To deploy to AWS Beanstalk, you need an IAM Policy with at least the following permissions:

```json
{ 
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "elasticbeanstalk:*",
                "ec2:*",
                "ecs:*",
                "ecr:*",
                "elasticloadbalancing:*",
                "autoscaling:*",
                "cloudwatch:*",
                "s3:*",
                "sns:*",
                "cloudformation:*",
                "dynamodb:*",
                "rds:*",
                "sqs:*",
                "iam:GetPolicyVersion",
                "iam:GetRole",
                "iam:PassRole",
                "iam:ListRolePolicies",
                "iam:ListAttachedRolePolicies",
                "iam:ListInstanceProfiles",
                "iam:ListRoles",
                "iam:ListServerCertificates",
                "acm:DescribeCertificate",
                "acm:ListCertificates",
                "codebuild:CreateProject",
                "codebuild:DeleteProject",
                "codebuild:BatchGetBuilds",
                "codebuild:StartBuild"
            ],
            "Resource": "*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "iam:AddRoleToInstanceProfile",
                "iam:CreateInstanceProfile",
                "iam:CreateRole"
            ],
            "Resource": [
                "arn:aws:iam::*:role/aws-elasticbeanstalk*",
                "arn:aws:iam::*:instance-profile/aws-elasticbeanstalk*"
            ]
        },
        {
            "Effect": "Allow",
            "Action": [
                "iam:AttachRolePolicy"
            ],
            "Resource": "*",
            "Condition": {
                "StringLike": {
                    "iam:PolicyArn": [
                        "arn:aws:iam::aws:policy/AWSElasticBeanstalk*",
                        "arn:aws:iam::aws:policy/service-role/AWSElasticBeanstalk*"
                    ]
                }
            }
        }
    ]
}
```
- These credentials can be provided in several ways:
	- A set of encrypted AWS credentials to embed into the pipelines YAML for deployment during CI, and manual deploys by the project release engineer/owner.
	- Pipelines can create a utility to store the credentials internally, then use them to generate a federated set of credentials that expire, and automatically embed them as environment variables within the container during the build.
	- Pipelines can create a utility to store the credentials internally, then provide a CLI command that takes the application zip file and version bump option. This could find configurations using the `.elasticbeanstalk` directory or internally (during AWS setup), and uses the [Beanstalk SDK](https://aws.amazon.com/documentation/elastic-beanstalk/) to automatically upload the package to the S3 bucket, create a version, and deploy the version.
- Deployment monitoring and alerting can be provided in several ways:
	- The release engineer can use the Beanstalk console to monitor, and manually set up Cloudwatch events for system notification.
	- Pipelines could create a CLI command to generate/monitor lifecycle events of the Beanstalk application components and provide that information within the job log, or as separate metadata on the job.

## Implementation Tasks
| Task | Ticket |
| --- | --- |
| Create repository | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |
| Initalize API and CORS | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |
| Create beanstalk application and npm scripts | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |
| Initalize Passport with Sqlite/RDS | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |
| YAML defined API flows | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |
| YAML defined Websocket flows | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |
| Replace Merver with new Mock API | [MS-XXXX](https://backlog.acquia.com/browse/MS-XXXX) |