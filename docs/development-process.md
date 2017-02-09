# Development Process

## Team Structure

1. Product Owners
    - Responsible for work delegation and vision for the product
2. Engineering Managers/Architects
    - Responsible for design and ownership of engineering talent. Core engineering contributor and technical leader
3. Engineers
    - Responsible for implementation of approved architectures
4. QA Engineers
    - Responsible for managing technical debt and quality assurance tools/strategies
5. UX Engineer
    - Provide UX guidance and review for screens

## Process

### Workflow

1. Sprint planning creates priority and assigns story/bug/yask in JIRA (MS project)
  - Inputs are validated for all tickets: [templates](#input-templates)
  - Click start progress on the JIRA Ticket
2. Fork the pipelines-ui repo (one time)
  - `git remote add upstream https://github.com/acquia/pipelines-ui.git`
3. Update upstream `git fetch upstream`
4. Switch to master `git checkout master`
5. Get upstream changes `git merge upstream/master`
6. Create your JIRA Branch `git checkout -b <Jira Story #>--<short story description>` (ex: MS-1111--do-good-work)
8. Do work: `devvy dev dev, dev-dev dev dev`
  - Reference often the following Style Guides and Best Practices:
      - [Angular Styleguide](https://angular.io/styleguide )
      - [Scalable & Modular Architecture for CSS](https://smacss.com/)
      - [Block__Element--Modifier](http://bem.info)
      - [Commit Message Style](https://github.com/angular/angular.js/blob/master/CONTRIBUTING.md#-git-commit-guidelines)
10. Quality checks
  - All UI code must be unit tested (90% code coverage in Karma/Istanbul)
      - `npm test`
      - `npm run coverage`
  - All UI code must pass lint
      - `npm run lint`
  - New or changes to features should be updated in the feature's readme
  - Work on bugs should include a root cause analysis in the JIRA ticket comment section
11. Prepare for PR
  - Get updates from upstream:
      - `git checkout master`
      - `git pull upstream/master`
  - If there were any new commits, rebase your branch
      - `git checkout MS-1111` (use your ticket number)
      - `git rebase master`
  - Squash your commits if desired
      - `git checkout`
      - `git rebase -i master`
  - Push to remote
      - `git push --set-upstream origin MS-1111` (use your ticket number)
12. Submitting your PR
  - Once you've committed and pushed all of your changes to GitHub, go to the page for your fork on GitHub, select your branch, and click the pull request button. If you need to make any adjustments to your pull request, just push the updates to GitHub. Your pull request will automatically track the changes on your branch and update.
  - **Your PR must start with the corresponding JIRA ticket number, then a description. EX: MS-1111--To-This-Thing (replace with your ticket number)** This allows JIRA to track the PR. Example [here](https://backlog.acquia.com/browse/MS-2271)
13. Code review
  - Use one of the defined [templates](#code-review-templates) as a guide
  - All feedback can be discussed, but it is the PR submitter's responsibility to make changes.
  - Review and feedback will only take place on GitHub. Any feedback left in other channels (email, chat, conversation, etc) will not be addressed by the developer responsible for the code submitted as a PR.
14. UI review
  - A UI review will ensure the consistency with the product owner vision and the Acquia visual design standards and practices.
  - This will use the feedback templates provided as a starting point.
  - All feedback can be discussed, but it is the PR submitter's responsibility to make changes.
  - Review and feedback will only take place on GitHub. Any feedback left in other channels (email, chat, conversation, etc) will not be addressed by the developer responsible for the code submitted as a PR.
15. When your PR is merged, close your JIRA ticket with the appropriate status.

## Team Events

### Pre Sprint Planning

Product owners, and engineering managers will determine priority epics for the sprint based on feedback from clients and internal members.

### Sprint Planning

The team will meet once a week and groom the backlog for bugs, stories, and tasks that align with the sprint's epic priorities. A story point value will be voted on, and assigned. An assignee will also be assigned.

### Scrum

A scrum will be performed by the scrum master to provide general updates on progress during the sprint to the product owners.

### Release/Demo Day

Every UI feature must be approved by Product Ownership before release to the customer.  Product Owners will use the staging environment to vet the product in it's current state, and be provided an itemized change log for review (generated from JIRA). After approval, all JIRA tickets should be marked with the release version.

### Retrospective

At the end of a sprint, the team will retro on sprint performance and process, and generate ideas for kaizen of the team.

## Templates

### Input Templates

#### Story

```markdown

h3. Story
- As <Persona>
- I would like <feature(s)>
- So that I can <solve a problem>

h3. Acceptance Criteria
- Itemized list of user experiences that satisfy the story

h3. Inputs
h4. Mockup
- URL to be provided. If no mockup provided, the development team can make one, and get signoff from a ux engineer/product owner
h4. API Strategy
- API considerations, endpoints, parameters, body content, and expected responses

h3. Notes
- A place to list any architecture considerations, dependency tickets, etc

```

#### Bug

```markdown

h3. Description
- Itemized description of the bug
h3. Reproduction Steps
- Itemized steps to reproduce
h3. Expected Outcome
- Itemized list of expected outcomes
- Root cause analysis
h3. Notes
- Any other notes

```

### Code Review Templates

#### General Development

```markdown

- [ ] Passes lint
- [ ] Passes unit tests
- [ ] Passes coverage gate
- [ ] DocBlocks included
- [ ] Code blocks commented
- [ ] Documented in readme
- [ ] Adheres to Angular style guide
- [ ] Adheres to SMACCS
- [ ] Has appropriate architecture
- [ ] Has performant architecture
- [ ] Implementation is aligned with acceptance criteria
- [ ] Implementation catches indirect user scenarios

(Provide doc links to support your feedback)

```

#### Integration Test Development

```markdown

- [ ] Passes eslint
- [ ] Uses the described endpoint
- [ ] Replicates the UI usage of the API
- [ ] Validates the response structure
- [ ] Validates the response data types
- [ ] Documented in integration Readme

(Provide doc links to support your feedback)

```

#### E2E Test Development

```markdown

- [ ] Passes eslint
- [ ] Validates the user scenario
- [ ] Accounts for indirect user scenarios
- [ ] Utilizes the page object pattern where appropriate
- [ ] Utilizes environment variables where appropriate for stage parity
- [ ] Utilizes parameters where appropriate
- [ ] Documented in associate module(s) Readme

(Provide doc links to support your feedback)

```

### Feedback Templates

#### VD/IA Review

```markdown

## Visual Design
- [ ] Theme colors
- [ ] Font choices
- [ ] Margin/padding choices
- [ ] Animation choices
- [ ] Behavior choices
- [ ] Asset choices
## Information Architecture
- [ ] Not missing information
- [ ] Information displayed in correct component type
- [ ] Error messaging is appropriate
- [ ] Wording choices are appropriate
- [ ] Follows accessibility standards
- [ ] Responsive layout
- [ ] Adaptive layout
- [ ] Appropriate user flow

(Provide doc links to support your feedback)

```
