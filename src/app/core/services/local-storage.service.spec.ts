/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {LocalStorageService} from './local-storage.service';

describe('LocalStorageService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [LocalStorageService]
    });
  });

  it('should create', inject([LocalStorageService], (service: LocalStorageService) => {
    expect(service).toBeTruthy();
  }));

  it('should set and get items from local storage', inject([LocalStorageService], (service: LocalStorageService) => {
    service.set('item', 'value');
    expect(service.get('item')).toEqual('value');
  }));

  it('should "namespace" local storage items to prevent collisions', inject([LocalStorageService], (service: LocalStorageService) => {
    expect(service.getKey('item')).toEqual('pipelines-dev-item');
  }));
});
