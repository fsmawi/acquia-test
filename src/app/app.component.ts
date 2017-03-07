import {Component} from '@angular/core';
import {AmplitudeService} from './core/services/amplitude.service';
import {LiftService} from './core/services/lift.service';
import {BugsnagService} from './core/services/bugsnag.service';
import {SegmentService} from './core/services/segment.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  constructor(
    private amp: AmplitudeService,
    private segmentService: SegmentService,
    private liftService: LiftService,
    private bugsnag: BugsnagService) { }
}
