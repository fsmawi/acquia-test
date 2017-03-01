// The file contents for the current environment will overwrite these during build.
// The build system defaults to the dev environment which uses `environment.ts`, but if you do
// `ng build --env=prod` then `environment.prod.ts` will be used instead.
// The list of which env maps to which file can be found in `angular-cli.json`.

export const environment = {
  name: 'dev',
  production: false,
  apiEndpoint: '',
  n3Key: '', // user supplied, or from cookies
  n3Secret: '', // user supplied, or from cookies
  headers: {},
  URL: '',
  auth: { // authentication parameters
    github: {
      oauthEndpoint: '',
      oauthTokenEndpoint: '',
      apiEndpoint: '',
      clientId: '',
      clientSecret: '',
      redirectUrl: '',
      scopes: ''
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
