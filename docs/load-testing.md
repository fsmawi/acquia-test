# Load Testing Pipelines

## Pre-requisites
You will need an IDE that can handle large file (Ex: 010 Editor) to create test files. You will need also a Chrome extension called [UX Profiler](https://chrome.google.com/webstore/detail/ux-profiler/pbggladnflcdnacpafmaahjemhnjmpjc?hl=en), which will help us to get time measurements of UX slow actions.

## Testing strategy
In order to test the load capacity we follow three scenarios:
1. Using log file with a big number of relatively small chunks.
2. Using log file with few but very big chunks.
3. Using log file combining the first two approaches.

## Creating YAML files 
1 - Log file with a big number of relatively small chunks:
Create a new yml file and put in only necessary routes for test (bakery auth, application info, job details and job log). For the job log route, use a set of 100 small chunks and keep duplicating it until getting the wished number of chunks.

2 - Log file with few but very big chunks:
Create a new file based on [log-chunks.yml](../test/api-mock-resources/log-chunks.yml). Isolate the text part in the message log, from `Executing step test` to `Exiting step test` in a separate file and keep duplicate this part until getting the whished chunk size, and replace the original part with it.

3 - Log file combining the first two approaches:
Create a new file based on [log-chunks.yml](../test/api-mock-resources/logs-chunks.yml), and follow same steps as the previous scenario without going far the chunk size (~100k). After that, just like the first scenario, keep duplicating the result log response until you get the whished file size.

## Executing Tests
For each scenario:
1. Run the Merver this the corresponding file.
2. Load jobs list and click on job ID to get the job details.
3. Wait and measure time until log is displayed.
4. Open UX Profiler tab in the DevTool and start performing UX actions (click to open collapsed chunks, scroll down and up...). UX Profiler will report on slow event that really affect UX.

## Reporting result
You can report result on the basis of:
* Time to load log.
* Responsive response
* UX action responses (click, scroll, drag & drop...)
