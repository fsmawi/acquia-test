import {HelpContentCategoryFilterPipe} from './help-content-category-filter.pipe';
import {HelpItem} from '../../models/help-item';

const helpCenterContent: Array<HelpItem> = [
  {
    id : 'PipelinesExamples',
    type: 'DOCUMENTATION',
    category: 'GENERAL',
    title: 'Pipelines Examples',
    description: `This repository contains example code and tutorials for Acquia Pipelines.`,
    externalLink: 'https://github.com/acquia/pipelines-examples'
  },
  {
    id : 'PipelinesDocumentation',
    type: 'DOCUMENTATION',
    category: 'GENERAL',
    title: 'Pipelines Documentation',
    description: `Introduction to Acquia Pipelines, a flexible and straightforward mechanism for 
      assembling, compiling, and governing codebases.` ,
    externalLink: 'https://docs.acquia.com/pipelines'
  },
  {
    id : 'TroubleshootingGuide',
    type: 'DOCUMENTATION',
    category: 'PERSONALIZED',
    title: 'Troubleshooting guide',
    description: `This page describes some of the approaches you can take in determining the causes 
      of errors or other problems with Acquia Pipelines.`,
    externalLink: 'https://docs.acquia.com/pipelines/troubleshooting'
  },
  {
    id : 'ReleaseNotes',
    type: 'DOCUMENTATION',
    category: 'PERSONALIZED',
    title: 'Release Notes - Acquia Pipelines',
    description: `Looking for the latest and greatest new features and changes to Acquia Pipelines?`,
    externalLink: 'https://docs.acquia.com/pipelines/release-notes'
  }
];

describe('HelpContentCategoryFilterPipe', () => {
  it('create an instance', () => {
    const pipe = new HelpContentCategoryFilterPipe();
    expect(pipe).toBeTruthy();
  });

  it('should return only filtered help content', () => {
    const pipe = new HelpContentCategoryFilterPipe();
    expect(pipe).toBeTruthy();
    expect(pipe.transform(helpCenterContent, 'GENERAL').length).toBe(2);
    expect(pipe.transform(helpCenterContent, 'PERSONALIZED').length).toBe(2);
    expect(pipe.transform(helpCenterContent, 'RANDOM-TEXT').length).toBe(0);
  });
});
