import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {MaterialModule} from '@angular/material';
import {RouterTestingModule} from '@angular/router/testing';

import {MomentModule} from 'angular2-moment';

import {ApplicationsListComponent} from './applications-list.component';
import {Application} from '../../models/application';
import {ErrorService} from '../../services/error.service';
import {PipelinesService} from '../../services/pipelines.service';
import {SharedModule} from '../../../shared/shared.module';
import {ElementalModule} from '../../../elemental/elemental.module';

class MockPipelinesService {

  application = new Application({
    'uuid': 'c674ff03-fcca-4f63-a235-6bfc115dd316',
    'name': 'test1',
    'hosting': {
      'type': 'test',
      'id': 'test:test'
    },
    'subscription': {
    },
    'organization': {
    },
    'latest_job': {
      'job_id': 'fc323ce8-004c-4763-843e-ac0583c04b25',
      'sitename': 'test:test',
      'pipeline_id': 'f4dcafb9-e1c4-4ee4-8575-0a40842158a0',
      'branch': 'master',
      'commit': 'e36c0207827011c04c4931c292f1aad653cb7f0e',
      'status': 'queued',
      'priority': null,
      'requested_by': 'exampleuser',
      'requested_at': 1483225200,
      'started_at': 0,
      'finished_at': 0,
      'duration': null,
      'exit_message': 'test exit message',
      'trigger': 'manual',
      'metadata': {
      }
    },
    'pipelines_enabled': true
  });

  getApplications() {
    return Promise.resolve([this.application]);
  }
}

describe('ApplicationsListComponent', () => {
  let component: ApplicationsListComponent;
  let fixture: ComponentFixture<ApplicationsListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ApplicationsListComponent ],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        ErrorService
      ],
      imports: [
        MaterialModule.forRoot(),
        ElementalModule,
        SharedModule,
        MomentModule,
        RouterTestingModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ApplicationsListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should get applications list', () => {
    expect(component).toBeTruthy();
    component.getApplications()
      .then(() => {
        expect(component.applications.length).toBe(1);
        fixture.detectChanges();
        const compiled = fixture.debugElement.nativeElement;
        expect(compiled.querySelector('#applications-list')).toBeTruthy();
      });
  });

});
