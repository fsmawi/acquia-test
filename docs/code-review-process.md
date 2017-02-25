# Code Review Process

A guide for reviewing code and having your code reviewed.

## Guidelines and Best Practices

### Everyone

* Accept that many programming decisions are opinions. Discuss tradeoffs, which you prefer, and reach a resolution quickly.
* Ask good questions; don't make demands. ("What do you think about ..." rather than "This isn't how we do ...")
  * Good questions avoid judgment and avoid assumptions about the author's perspective.
* Ask for clarification if a coded decision is unclear. ("I don't understand. Can you clarify?")
* Avoid selective ownership of code. ("mine", "not mine", "yours")
* Avoid using terms that could be seen as referring to personal traits. Assume everyone is intelligent and well-meaning.
* Be explicit. Remember, people don't always understand your intentions online.
* Be humble. ("I'm not sure - let's look it up.")
* Actively avoid hyperbole. ("always", "never", "endlessly", "nothing")
* Avoid sarcasm — it does not translate well via text.
* Keep it real. If emoji, animated gifs, or humor isn't your thing, don't force it.
* Talk synchronously (e.g. interactive chat [Zoom, Hangout, etc], screen sharing, or in person) if there are too many "I don't understand" or "Alternative solution:" comments. When a synchronous discussion takes place, post a follow-up comment summarizing the discussion and whom it took place with.
* Keep all feedback comments and discussions in context of the review; don't start discussing code review feedback in chat or other obfuscated-to-the-team areas like email.

### Having Your Code Reviewed

* Be grateful for the reviewer's suggestions. ("Good call. I'll make that change.")
* Don't take it personally. The review is of the code, not you. This is a learning experience, nothing more.
* Explain why the code exists. ("It's like that because of these reasons. Would it be more clear if I rename this class/file/method/variable?")
* Extract some changes and refactors into future tickets/stories. (code style, words usage, naming things, etc.)
* Link to the code review from the ticket/story. (A manual Jira link on the story shouldn't be necessary if your branch name starts with the ticket number. Alternatively, you can also mention the Jira Story number in the commit message and have the same effect.)
* Use the [Angular Git Commit Guideline](https://github.com/angular/angular.js/blob/master/CONTRIBUTING.md#commit) for:
  * commit messages - clearly outline the type of changes (fix, chore, docs, etc) being made and list all areas affected by the change in the message's body/summary
  * pull requests - the title and the summary of the pull request should be used as the intended "merged commit" message that can be leveraged and extended by the developer that will be responsible for deploying the approved changes. It is especially important that the pull request summary include the "footer" mentioned in the Angular Guidelines (Resolves MS-XX, etc)
* Push commits based on earlier rounds of feedback as isolated commits to the branch. Do not squash! (Squashing your commits will occur when another team member merges your code.) Reviewers should be able to read individual updates based on their earlier feedback.
* Seek to understand the reviewer's perspective if there is confusion. (Synchronous meetings help when there is confusion.)
* Attempt to respond to every comment; emoji, gif, "acknowledged", etc.
* Do NOT merge your own code. This is specifically the responsibility of another team member who has approved the review and waited for all Continuous Integration (Pipelines, Jenkins, TravisCI, etc.) tests to pass.

### Reviewing Code

Understand why the change is necessary (fixes a bug, improves the user experience, refactors the existing code). Then:

* Communicate which ideas you feel strongly about and those you don't.
* Identify ways to simplify the code while still solving the problem and meeting the corresponding Jira Story's "Acceptance Criteria."
* If discussions turn too philosophical or academic, move the discussion offline. In the meantime, let the author make the final decision on alternative implementations.
* Offer alternative implementations, but assume the author already considered them. ("What do you think about using a custom validator here?")
* Seek to understand the author's perspective.
* Sign off on the pull request by using GitHub's interface action of "Approve Changes".
* When it has been deemed appropriate ("changes accepted" or otherwise) by the team and at least 2 of the requested reviewers, all Continuous Integration and Deployment tests have passed, and the author has addressed (not necessarily applied) all feedback it is time to "merge and squash," releasing the pull request to the appropriate branch.

## Process and Workflow

You've read the [development process](development-process.md) documentation and now you're ready to have the team review your efforts. You've pushed a feature branch to your fork and it is ready for review. Great Job! Now what?

It is required that all GitHub pull requests be attached and scoped to a single Jira Story. In an attempt to lessen the amount of code that will be reviewed, and to also ensure a separation of concerns should work need to be rolled back (for whatever reason). As noted in the [development process](development-process.md), your feature branch is named according to the standard outlined: The Jira Story number followed by a brief description of the work being completed (ie. MS-XX--this-does-stuff). Naming the branch in this way ensures that the pull request is linked to the appropriate Jira Story. It is rarely acceptable to meet the "Acceptance Criteria" for multiple Jira Stories in one pull request — for the reasons outlined previously — but can be addressed by the Scrum team on a case-by-case basis.

For Pipelines UI, the master branch of this repository is the canonical source; the pull request you create should request that the feature branch be merged into master. As outlined in section 7 of the development process's [workflow](development-process.md#code-review-templates), your feature branch's commit messages should be formatted according to [Angular's convention](https://github.com/angular/angular.js/blob/master/CONTRIBUTING.md#-git-commit-guidelines). The same is true for the pull request's summary section. The title of the summary is essentially the first line of the "message" while the body and resolutions are outlined in the summary field. This pull request summary will be used — and updated with pertinent feedback changes — when "squashing and committing" via the GitHub UI. It is vital that the title _and_ summary of a pull request be provided; this lessens confusion among the team by reiterating the goals for the Jira Story and adding context to the changes being made.

When identifying members of the team that have pertinent expertise to the code being submitted for review, you should keep in mind that you need at least two members to approve the pull request. It is also recommended that all members of the team — technical or otherwise — be included as a reviewer. This allows all team members to have insight to the changes being introduced and ensures complete transparency in developments; it also provides a learning opportunity for other developers whom don't possess the level of expertise required to build the feature/changes being introduced.

While reviewers may leave inline-comments on the files changed, GitHub has a code review feature that can be leveraged as well. Regardless of the method chosen by reviewers, all feedback should be addressed and/or acknowledged. Following the [code review workflow](https://help.github.com/articles/about-pull-request-reviews/) [outlined by GitHub](https://help.github.com/articles/reviewing-proposed-changes-in-a-pull-request/) is sufficient (the "Further Reading" section at the bottom of the linked GitHub documentation pages will provide useful information that is applicable to the review process).

When reviewing a pull request authored and submitted by a teammate, follow the [checklists](development-process.md#code-review-templates) outlined by the development process; this checklist can be added to the "Code Review" summary message when you submit your review, but is not required. The intention is that as a reviewer, you have pulled the feature branch under review to your local development environment and manually tested it, ensuring that it works as intended and meets all acceptance criteria outlined in the corresponding Jira Story.

## Process Scenarios and "Troubleshooting"

### There are necessary updates to the pull request being reviewed in order to successfully achieve the corresponding Jira Story's "Acceptance Criteria."

First, attempt to formulate your feedback as a general comment on the pull request if you feel that there is not an appropriate "changed file" to comment on. This is especially important when you are attempting is to address feedback that was previously presented and/or discussed on the pull request that you are reviewing. It is rare that a new pull request needs to be made, especially when it will be attached to the same Jira Story (already in review) from a developer that is not responsible for completing said Jira Story.

If in fact you find that important information in a file that is not part of the review needs to be updated, there are a couple ways to go about this:

1. Pull the pull request author's feature branch locally and create a new feature branch based on that work: `git checkout -b <Same Story Number>--<description of updates to be made>`. You can then create a pull request to the original author's feature branch that is already in review. These changes will then be reviewed by the original author, merged in and then become part of the overall review. **This method should be reserved for large, overly complicated decisions where developer expertise plays a major role in the implementation of a feature.**

1. Alternatively, if the changes to files that are not part of the active review are valid and support the "Acceptance Criteria" outlined by the Jira Story, you may pull the author's feature branch locally, complete the necessary updates, and then commit directly to that author's feature branch (using the defined Git commit message convention). **While this may seem strange committing code to another developer's feature branch that is under active review, this will automatically update the active pull request and make the changes transparent to all reviewers.**

### The pull request that you are viewing — even though it may meet the Jira Story's "Acceptance Criteria" — is not executed and implemented in a way that is conducive of future feature development and may actually be introducing immediate technical debt that will cause long-term problems for the software to meets it's goals.

Refactors happen, and it is important that the pull request author fully understands the ramifications of what they have developed; after all, the code review process is meant to be a learning experience as much as it is to ensure code quality. If this situation is to occur, and you are unable to provide productive feedback that the author is able to grok, it may be necessary to scrap the work that has been completed. **This decision will be made by the team and not by one sole individual.**

After the decision to refactor has been made it is extremely important that the original author be included in the redevelopment of the feature according to the previously misunderstood guidelines or standards; this does not mean that the original developer is also responsible for implementing the refactor. In order for the team to be successful, we need to share as much knowledge as possible; many generalists and a few specialists is always better then only the latter. Remember, one of the goals of the code review is to have these difficult discussions so that we can learn from them and update the process and documentation where necessary. This is outlined by the fourth bullet point in the "Having Your Code Reviewed" section above: "Extract some changes and refactors into future tickets/stories. (code style, words usage, naming things, etc.)"
