import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';

declare const localStorage;

@Injectable()
export class LocalStorageService {

  /**
   * Key prefix to prevent overlaps
   */
  prefix: string;

  /**
   * Builds the service
   */
  constructor() {
    this.prefix = `pipelines-${environment.name}-`;
  }

  /**
   * Set a local storage item
   * @param key
   * @param value
   */
  set(key: string, value: string) {
    localStorage.setItem(this.getKey(key), value);
  }

  /**
   * Get a local storage item
   * @param key
   * @returns {SVGNumber|string|null|SVGPoint|SVGLength|SVGTransform|any}
   */
  get(key) {
    return localStorage.getItem(this.getKey(key));
  }

  /**
   * Generate the prefixed key
   * @param key
   * @returns {string}
   */
  getKey(key: string) {
    return `${this.prefix}${key}`;
  }
}
