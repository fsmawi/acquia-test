/**
 * Github Status model
 */
export class GithubStatus {

  /**
   * App Id for this status
   */
  appId: string;

  /**
   * Connection flag
   */
  connected: boolean;

  /**
   * Github repo connected to if available
   */
  repo_url: string;

  /**
   * build the model
   *
   * Generic response from /ci/github/status:
   {
     "fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0": {
       "connected": true,
       "repo_url": "git@github.com:acquia/pipelines-ui.git"
     }
   }
   *
   *
   * @param appId
   * @param obj
   */
  constructor(appId: string, obj: any) {
    Object.assign(this, obj[appId]);
    this.appId = appId;
  }
}
