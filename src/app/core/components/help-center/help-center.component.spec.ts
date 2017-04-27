import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {ObservableMedia} from '@angular/flex-layout';
import {FormsModule} from '@angular/forms';

import {HelpCenterComponent} from './help-center.component';
import {HelpCenterService} from '../../services/help-center.service';
import {LiftDirective} from '../../../shared/directives/lift.directive';
import {SegmentDirective} from '../../../shared/directives/segment.directive';
import {TrackDirective} from '../../../shared/directives/track.directive';
import {ElementalModule} from '../../../elemental/elemental.module';
import {HelpContentCategoryFilterPipe} from './help-content-category-filter.pipe';

class MockOberservableMedia {
  isActive(screenSize: string) {
    return true;
  }
}

class MockHelpCenterService {
  show() {
    return true;
  }
}

describe('HelpCenterComponent', () => {
  let component: HelpCenterComponent;
  let fixture: ComponentFixture<HelpCenterComponent>;

  beforeEach(async(() => {

    TestBed.configureTestingModule({
      declarations: [
        HelpCenterComponent,
        LiftDirective,
        SegmentDirective,
        TrackDirective,
        HelpContentCategoryFilterPipe
      ],
      providers: [
        {provide: HelpCenterService, useClass: MockHelpCenterService},
        {provide: ObservableMedia, useClass: MockOberservableMedia},
        HelpContentCategoryFilterPipe
        ],
      imports: [
        ElementalModule,
        FormsModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(HelpCenterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should show the help center drawer', () => {
    expect(component).toBeTruthy();
    component.show();
    expect(component.showHelpCenter).toBe(true);
  });

  it('should close the help center drawer', () => {
    expect(component).toBeTruthy();
    component.show();
    expect(component.showHelpCenter).toBe(true);
  });

  it('should filter the help content', () => {
    expect(component).toBeTruthy();

    component.helpContent = [ {
      id : 'PipelinesExamples',
      type: 'DOCUMENTATION',
      category: 'GENERAL',
      title: 'Pipelines Examples',
      description: `This repository contains example code and tutorials for Acquia Pipelines.`,
      externalLink: 'https://github.com/acquia/pipelines-examples'
    }];

    component.filter();
    expect(component.filteredHelpContent.length).toBe(1);

    component.filterText = 'random-text';
    component.filter();
    expect(component.filteredHelpContent.length).toBe(0);

    component.filterText = 'Examples';
    component.filter();
    expect(component.filteredHelpContent.length).toBe(1);
  });
});
