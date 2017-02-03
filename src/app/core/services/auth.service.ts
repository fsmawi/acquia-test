import {Injectable} from '@angular/core';
import 'rxjs/add/observable/of';
import 'rxjs/add/operator/do';
import 'rxjs/add/operator/delay';
import {environment} from '../../../environments/environment';

@Injectable()
export class AuthService {

  // HACK TODO: Provide actual credential login using cookie strategy

  get isLoggedIn(): boolean {
    return environment.n3Key && environment.n3Secret ? true : false;
  };
}
