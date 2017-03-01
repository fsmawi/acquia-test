// The file contents for the current environment will overwrite these during build.
// The build system defaults to the dev environment which uses `environment.ts`, but if you do
// `ng build --env=prod` then `environment.prod.ts` will be used instead.
// The list of which env maps to which file can be found in `angular-cli.json`.

export const environment = {
  name: 'mock',
  production: false,
  apiEndpoint: 'http://localhost:3000',
  n3Key: '', // user supplied, or from cookies
  n3Secret: '', // user supplied, or from cookies
  headers: {},
  URL: 'http://localhost:4200',
  auth: { // authentication parameters
    github: {
      oauthEndpoint: 'https://github.com/login/oauth/authorize',
      oauthTokenEndpoint: 'https://github.com/login/oauth/access_token',
      apiEndpoint: 'https://api.github.com',
      clientId: '0feb9b9b388fd7d05d2c',
      clientSecret: '42d64969ab4a28fc363124675d9fc193d17345c1',
      redirectUrl: 'http://localhost:3000/callback',
      scopes: 'user,repo'
    }
  },
  lift: {
    account_id: 'ACQUIAWEB',
    site_id: 'pipelines-nonprod',
    liftAssetsURL: 'https://lift3assets.lift.acquia.com/stable',
    liftDecisionAPIURL: 'https://us-east-1-decisionapi.lift.acquia.com',
    authEndpoint: 'https://us-east-1-oauth2.lift.acquia.com/authorize',
    contentReplacementMode: 'trusted',
    Profiles: {
      UDFFieldname: ''
    }
  },
  amplitudeAPIKey: '',
  segmentWriteKey: '',
  authRedirect: ''
};
