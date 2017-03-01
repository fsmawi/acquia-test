import {Component, OnInit, trigger, state, style, transition, animate} from '@angular/core';
import {animations} from '../../core/animations';

@Component({
  selector: 'e-card',
  templateUrl: './card.component.html',
  styleUrls: ['./card.component.scss'],
  animations: animations
})
export class CardComponent implements OnInit {

  constructor() {
  }

  ngOnInit() {
  }

}
