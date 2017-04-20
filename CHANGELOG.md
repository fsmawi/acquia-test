# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

<a name="0.2.18"></a>
## [0.2.18](https://github.com/acquia/pipelines-ui/compare/v0.2.17...v0.2.18) (2017-04-20)


### Bug Fixes

* **application.module:** Added missing import ([6a68b87](https://github.com/acquia/pipelines-ui/commit/6a68b87))



<a name="0.2.17"></a>
## [0.2.17](https://github.com/acquia/pipelines-ui/compare/v0.2.16...v0.2.17) (2017-04-20)



<a name="0.2.16"></a>
## 0.2.16 (2017-04-19)


### Bug Fixes

* **action-header:** Removed tooltip ([4485eb8](https://github.com/acquia/pipelines-ui/commit/4485eb8))
* **application.component:** Used new status route (#107) ([294431a](https://github.com/acquia/pipelines-ui/commit/294431a))
* **auth-github.component:** Added check for undefined string (#73) ([963e3e8](https://github.com/acquia/pipelines-ui/commit/963e3e8))
* **auth-github.component:** Added workaround for API usage ([7674d48](https://github.com/acquia/pipelines-ui/commit/7674d48))
* **auth-tokens.component:** Fixed small card size ([d3c31ea](https://github.com/acquia/pipelines-ui/commit/d3c31ea))
* **auth.service:** Added global headers for mocking auth ([b8242ce](https://github.com/acquia/pipelines-ui/commit/b8242ce))
* **bugsnag.service.ts:** add globl variable in index.html (#104) ([2de8ee4](https://github.com/acquia/pipelines-ui/commit/2de8ee4))
* **bugsnag.service.ts:** dynamic injection of the Bugsnag script (#99) ([4b4ccf4](https://github.com/acquia/pipelines-ui/commit/4b4ccf4)), closes [#99](https://github.com/acquia/pipelines-ui/issues/99)
* **core.module:** Core import bug ([eba50e9](https://github.com/acquia/pipelines-ui/commit/eba50e9))
* **Default branch master fix:** Default branch master fix ([010bd94](https://github.com/acquia/pipelines-ui/commit/010bd94))
* **dependencies:** add package.json and bower.json ([5783783](https://github.com/acquia/pipelines-ui/commit/5783783))
* **E2E - Bakery SSO and Cloud integration:** E2E - Bakery SSO and Cloud integration (#121) ([5d6d1b8](https://github.com/acquia/pipelines-ui/commit/5d6d1b8))
* **e2e-test:** job list and job details (#119) ([6dafd9b](https://github.com/acquia/pipelines-ui/commit/6dafd9b))
* **e2e.core:** Added scroll into view ([5e6fe11](https://github.com/acquia/pipelines-ui/commit/5e6fe11))
* **e2e.core:** Added strict statement ([a666bb6](https://github.com/acquia/pipelines-ui/commit/a666bb6))
* **e2etesting:** fix eslint ([4f094c7](https://github.com/acquia/pipelines-ui/commit/4f094c7))
* **Fixed : Wierd refresh interval with no jobs:** MS-2641 (#125) ([b1176b4](https://github.com/acquia/pipelines-ui/commit/b1176b4)), closes [#125](https://github.com/acquia/pipelines-ui/issues/125)
* **Fixed Error Cannot read property 'nativeElement' of undefined:** Fixed Error Cannot read property (#152) ([d417787](https://github.com/acquia/pipelines-ui/commit/d417787)), closes [#152](https://github.com/acquia/pipelines-ui/issues/152)
* **Fixed null check on metadata:** Fixed null check on metadata ([d4ad6b1](https://github.com/acquia/pipelines-ui/commit/d4ad6b1))
* **github-dialog-repositories.component.html:** Add context to repo filter input ([8cf3566](https://github.com/acquia/pipelines-ui/commit/8cf3566))
* **github.component:** fix spacing issue ([d84eb29](https://github.com/acquia/pipelines-ui/commit/d84eb29))
* **github.component:** use the same "spinner" as Cloud UI ([a7f029a](https://github.com/acquia/pipelines-ui/commit/a7f029a))
* **integration:** move common helper file to integration directory & add colors ([c4253f8](https://github.com/acquia/pipelines-ui/commit/c4253f8))
* **job-list.component.html:** Update job id table header label to read "Job" rather than "Build" (#85) ([78eb6c4](https://github.com/acquia/pipelines-ui/commit/78eb6c4))
* **job-summary:** Fixed duration bug (#179) ([20fbcb6](https://github.com/acquia/pipelines-ui/commit/20fbcb6)), closes [#179](https://github.com/acquia/pipelines-ui/issues/179)
* **jobs-detail:** Fixed Unsub bug ([e017ea1](https://github.com/acquia/pipelines-ui/commit/e017ea1))
* **jobs-detail:** Minor usability fix ([9d8664e](https://github.com/acquia/pipelines-ui/commit/9d8664e))
* **jobs-detail:** Removed flappy tests ([74f30f2](https://github.com/acquia/pipelines-ui/commit/74f30f2))
* **no-jobs.component:** add tracking page view (#166) ([58a4da7](https://github.com/acquia/pipelines-ui/commit/58a4da7))
* **pipelines:** Fixed creds ([67a17d5](https://github.com/acquia/pipelines-ui/commit/67a17d5))
* **pipelines:** Fixed E2E Launch ([97b3ee9](https://github.com/acquia/pipelines-ui/commit/97b3ee9))
* **pipelines:** Fixed environment vars ([f600f63](https://github.com/acquia/pipelines-ui/commit/f600f63))
* **pipelines:** Fixed syntax ([e9f32cc](https://github.com/acquia/pipelines-ui/commit/e9f32cc))
* **pipelines:** Fixing auto release ([b934d76](https://github.com/acquia/pipelines-ui/commit/b934d76))
* **pipelines:** Updated Creds ([9ffc0b8](https://github.com/acquia/pipelines-ui/commit/9ffc0b8))
* **pipelines.service:** Added deploy_vcs_path param ([520be31](https://github.com/acquia/pipelines-ui/commit/520be31))
* **UX:** Debug ([c0b1620](https://github.com/acquia/pipelines-ui/commit/c0b1620))
* **UX:** Debug running pipelines-deploy ([b3dfe46](https://github.com/acquia/pipelines-ui/commit/b3dfe46))
* **UX:** disable buttons after click + fix css issues (#96) ([e024329](https://github.com/acquia/pipelines-ui/commit/e024329)), closes [#96](https://github.com/acquia/pipelines-ui/issues/96)
* **UX:** Fix overflow bug in the repos model (Firefox) (#75) ([2f7ec18](https://github.com/acquia/pipelines-ui/commit/2f7ec18)), closes [#75](https://github.com/acquia/pipelines-ui/issues/75)
* **UX:** fix small overflow bug for xs devices ([d523d30](https://github.com/acquia/pipelines-ui/commit/d523d30))
* **UX:** remove basic auth when viewing the site on cloud ([9505f66](https://github.com/acquia/pipelines-ui/commit/9505f66))
* **UX:** update oauth finish url to use cloud (#76) ([5896fd3](https://github.com/acquia/pipelines-ui/commit/5896fd3))
* **UX:** use N3 creds ([cec579a](https://github.com/acquia/pipelines-ui/commit/cec579a))


### Features

* **Ability to start a job:** MS-2664: Ability to start a job ([974e8c7](https://github.com/acquia/pipelines-ui/commit/974e8c7))
* **Ability to start a new job:** MS-2664: Ability to start a new job ([2190973](https://github.com/acquia/pipelines-ui/commit/2190973))
* **acquia-pipelines.yaml:** deploy to Acquia Git from Pipelines (#110) ([e33db61](https://github.com/acquia/pipelines-ui/commit/e33db61))
* **Added 'Remove Auth' Button:** MS-2546: Added remove auth button ([633120f](https://github.com/acquia/pipelines-ui/commit/633120f))
* **Added Feature Flag:** Added Feature Flag ([e35e836](https://github.com/acquia/pipelines-ui/commit/e35e836))
* **Added Tooltip for the icons:** MS-2661: Added tooltip for the icons ([1c48d66](https://github.com/acquia/pipelines-ui/commit/1c48d66))
* **AmplitudeService:** Added Amplitude integration (#48) ([1401d25](https://github.com/acquia/pipelines-ui/commit/1401d25))
* **animations.ts:** Add fadeInUp ngAnimate array for use within the card component ([b8d0196](https://github.com/acquia/pipelines-ui/commit/b8d0196))
* **application.component:** Added re-authorize button (#88) ([0376db9](https://github.com/acquia/pipelines-ui/commit/0376db9))
* **application.service:** Create a State Service ([8682f87](https://github.com/acquia/pipelines-ui/commit/8682f87))
* **auth-tokens.module:** Updated with bakery logic ([4e1b7ef](https://github.com/acquia/pipelines-ui/commit/4e1b7ef)), closes [#63](https://github.com/acquia/pipelines-ui/issues/63)
* **Deployment action added for jobs list:** MS-2541 (#111) ([49af45f](https://github.com/acquia/pipelines-ui/commit/49af45f))
* **docs:** tracking no jobs leave events in amplitude ([9f154c8](https://github.com/acquia/pipelines-ui/commit/9f154c8))
* **E2E-TEST:** e2e scenario for SSO Bakery implementation ([c777172](https://github.com/acquia/pipelines-ui/commit/c777172))
* **elemental progress:** Add progress indicator component ([d15f838](https://github.com/acquia/pipelines-ui/commit/d15f838)), closes [#29](https://github.com/acquia/pipelines-ui/issues/29)
* **error.service:** Added Bugsnag support ([3547da4](https://github.com/acquia/pipelines-ui/commit/3547da4)), closes [#27](https://github.com/acquia/pipelines-ui/issues/27)
* **errorreporting:** report All API error responses to bugsnag ([2547fbf](https://github.com/acquia/pipelines-ui/commit/2547fbf))
* **Filter shown jobs in jobs list:** MS-2744 (#167) ([ac0d148](https://github.com/acquia/pipelines-ui/commit/ac0d148))
* **github.component:** create alert model & remove initAlerts function ([cd5c671](https://github.com/acquia/pipelines-ui/commit/cd5c671))
* **github.component:** display messages as page level success ([8c28992](https://github.com/acquia/pipelines-ui/commit/8c28992))
* **help-center.component.ts:** Added Help Center Drawer (#183) ([780939e](https://github.com/acquia/pipelines-ui/commit/780939e))
* **help-center.component.ts:** Added Help Center Drawer (#183) ([9143391](https://github.com/acquia/pipelines-ui/commit/9143391))
* **help-center.component.ts:** Added Help Center Drawer (#183) ([dc69c2c](https://github.com/acquia/pipelines-ui/commit/dc69c2c))
* **help-center.component.ts:** Added version number at bottom of the help drawer content ([636fbf3](https://github.com/acquia/pipelines-ui/commit/636fbf3))
* **htaccess:** Forced HTTPS (#53) ([1af0c6a](https://github.com/acquia/pipelines-ui/commit/1af0c6a))
* **Job Details : VCS and  Deployments:** MS2336 (#102) ([512e455](https://github.com/acquia/pipelines-ui/commit/512e455))
* **job-detail.component:** MS-2539: Label font-size updated to bigger font s (#112) ([2c28d71](https://github.com/acquia/pipelines-ui/commit/2c28d71))
* **job-status:** Add 'terminated' state and update icons related to termination (#117) ([4b57850](https://github.com/acquia/pipelines-ui/commit/4b57850))
* **job-summary.component:** Added dynamic duration ([801bc2f](https://github.com/acquia/pipelines-ui/commit/801bc2f)), closes [#61](https://github.com/acquia/pipelines-ui/issues/61)
* **jobs-detail:** Added log chunking ([d2d152b](https://github.com/acquia/pipelines-ui/commit/d2d152b))
* **jobs-detail.component:** Realtime logs ([276a027](https://github.com/acquia/pipelines-ui/commit/276a027))
* **jobs.component.js:** MS 2757 - Allow actions to be performed on job details (#174) ([7fe7a7e](https://github.com/acquia/pipelines-ui/commit/7fe7a7e))
* **jobs.component.ts:** help about how to start a job (#106) ([a56b0fa](https://github.com/acquia/pipelines-ui/commit/a56b0fa))
* **jon-summary.component:** added userinfo in job-summary table (#149) ([3dffa99](https://github.com/acquia/pipelines-ui/commit/3dffa99))
* **landing-page.module:** Added a landing page (#79) ([24f8d30](https://github.com/acquia/pipelines-ui/commit/24f8d30))
* **landing.module:** MS-2235 : Added docs ([9142217](https://github.com/acquia/pipelines-ui/commit/9142217))
* **Logs: Scroll to bottom link added:** MS-2630 : Scroll to bottom logs (#129) ([68646c2](https://github.com/acquia/pipelines-ui/commit/68646c2))
* **MS-2661: Added VCS type icon and repo name:** MS-2661: Added VCS type icon and repo name ([06220cb](https://github.com/acquia/pipelines-ui/commit/06220cb))
* **no-jobs.component:** create Amplitude chart for No jobs => startjob (#168) ([a8e5e0e](https://github.com/acquia/pipelines-ui/commit/a8e5e0e))
* **no-jobs.component:** First experience - No Jobs ([5b9be05](https://github.com/acquia/pipelines-ui/commit/5b9be05))
* **pipelines:** Added E2E Command (#40) ([177919c](https://github.com/acquia/pipelines-ui/commit/177919c))
* **pipelines:** Added Integration Tests (#47) ([aed23b8](https://github.com/acquia/pipelines-ui/commit/aed23b8))
* **PR Number Shown in Branch Column:** MS-2678: Showing PR Number in Branch Column ([0ea87f6](https://github.com/acquia/pipelines-ui/commit/0ea87f6))
* **progress:** Add an animated component to signify page transition (#87) ([f0c0034](https://github.com/acquia/pipelines-ui/commit/f0c0034))
* **Remove Github Authentication:** MS-2546: Remove github auth ([0c02f62](https://github.com/acquia/pipelines-ui/commit/0c02f62))
* **Removed Githib Auth Service Added:** MS-2546: Remove github auth service added ([961c555](https://github.com/acquia/pipelines-ui/commit/961c555))
* **scripts/parallel-e2e:** E2E Parallel Execution (#97) ([f922896](https://github.com/acquia/pipelines-ui/commit/f922896))
* **segment:** Added some segment events (#94) ([2ba73e3](https://github.com/acquia/pipelines-ui/commit/2ba73e3))
* **Segment Analytics Added:** MS-2369 : Segment Analytics Added (#89) ([67590bd](https://github.com/acquia/pipelines-ui/commit/67590bd))
* **titles:** added cloud like titles to pages ([15e6086](https://github.com/acquia/pipelines-ui/commit/15e6086))
* **top-level-navigation.component:** Added top level navigation for standalone application (#180) ([079e223](https://github.com/acquia/pipelines-ui/commit/079e223))
* **ux:** replace the button with dot loader whenever user clicks on start button (#170) ([b709d36](https://github.com/acquia/pipelines-ui/commit/b709d36))
* **UX:** clear varnish after deployment from ci ([2399991](https://github.com/acquia/pipelines-ui/commit/2399991))
* **UX:** Create first UX experience connection flow (#148) ([ca3f8b0](https://github.com/acquia/pipelines-ui/commit/ca3f8b0))
* **UX:** display error message returned from API ([0db8b64](https://github.com/acquia/pipelines-ui/commit/0db8b64))
* **UX:** Moved all production assets to cloud front (#194) ([af4eb0a](https://github.com/acquia/pipelines-ui/commit/af4eb0a))
* Add CORS proxy ([601d745](https://github.com/acquia/pipelines-ui/commit/601d745)), closes [#25](https://github.com/acquia/pipelines-ui/issues/25)
* **UX:** new Github connection flow ([60fff86](https://github.com/acquia/pipelines-ui/commit/60fff86))
* **UX:** Pipelines Configuration page ([29a9c59](https://github.com/acquia/pipelines-ui/commit/29a9c59))
* **UX:** replace flash message with Alert in github get repos ([c3b28d4](https://github.com/acquia/pipelines-ui/commit/c3b28d4))
* **UX:** unify navigation throughout the iframe (#184) ([7ded3f6](https://github.com/acquia/pipelines-ui/commit/7ded3f6))
* **UX:** use new api version ([4513224](https://github.com/acquia/pipelines-ui/commit/4513224))


### Performance Improvements

* **auth.service:** Cached bakery (#95) ([d2f3ebf](https://github.com/acquia/pipelines-ui/commit/d2f3ebf)), closes [#95](https://github.com/acquia/pipelines-ui/issues/95)
