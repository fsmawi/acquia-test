import {Injectable} from '@angular/core';

@Injectable()
export class N3Service {

  /**
   * Environments for the application
   * @type {Array}
   */
  environments =  [
        {
          'id': '24-a47ac10b-58cc-4372-a567-0e02b2c3d470',
          'label': 'Dev',
          'name': 'dev',
          'application': {
            'name': 'Pipelines UI',
            'uuid': 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'
          },
          'domains': [
            'sitedev.hosted.acquia-sites.com', 'example.com'
          ],
          'active_domain': 'example.com',
          'default_domain': 'sitedev.hosted.acquia-sites.com',
          'image_url': 'https://api.url2png.com/v6/A2398ASDKJSD2/06a6fdde4c93979fb70fd850118f36a5/png/?url=my-domain.com',
          'status': 'normal',
          'vcs': {
            'type': 'git',
            'path': 'master',
            'url': 'site@svn-3.hosted.acquia-sites.com:site.git'
          }
        }
      ];


  constructor() {
  }

  getEnvironments(appId: string) {
    return Promise.resolve(this.environments);
  }

}
