export const environment = {
  name: 'prod',
  production: true,
  standalone: window.self === window.top,
  apiEndpoint: 'https://api.pipelines.acquia.com',
  n3Key: '', // user supplied, or from cookies
  n3Secret: '', // user supplied, or from cookies
  headers: {
    'X-ACQUIA-PIPELINES-N3-ENDPOINT': 'https://cloud.acquia.com'
  },
  URL: 'https://pipelines.acquia.com', // redirect url
  auth: { // authentication parameters for oauth
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
    site_id: 'pipelines-prod',
    liftAssetsURL: 'https://lift3assets.lift.acquia.com/stable',
    liftDecisionAPIURL: 'https://us-east-1-decisionapi.lift.acquia.com',
    authEndpoint: 'https://us-east-1-oauth2.lift.acquia.com/authorize',
    contentReplacementMode: 'trusted',
    Profiles: {
      UDFFieldname: ''
    }
  },
  bugsnagAPIKey: '53277c40f7254811573110c3ec847eec',
  amplitudeAPIKey: '6d3691615064031e356c16691f148e34',
  segmentWriteKey: 'Acn6k4EbYaeBzUsKnBZqqs9NQlccqeoq',
  authCloudRedirect: 'https://cloud.acquia.com',
  authAccountRedirect: 'https://accounts.acquia.com'
};
