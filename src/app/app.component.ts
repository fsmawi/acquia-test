import {Component} from '@angular/core';
import {AmplitudeService} from './core/services/amplitude.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  constructor(private amp: AmplitudeService) {
  }
}
