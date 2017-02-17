export const environment = {
  production: true,
  apiEndpoint: 'https://pipeline-api-production.pipeline.services.acquia.io',
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
  amplitudeAPIKey: '59e1dbd5afbc7c7c22c2a144fd7b5732'
};
