import {trigger, state, style, transition, animate} from '@angular/animations';

export const animations = [
  trigger('fadeInUp', [
    state('in', style({
      transform: 'translate(0, 0)',
      opacity: 1,
    })),
    transition(':enter', [
      style({
        transform: 'translate(0, 10px)',
        opacity: 0,
      }),
      animate(500)
    ])
  ]),
  trigger('slideInLeft', [
    state('in', style({
      transform: 'translate(0, 0)'
    })),
    transition(':enter', [
      style({
        transform: 'translate(100%, 0)'
      }),
      animate('300ms ease-out')
    ]),
    transition(':leave', [
      animate('300ms ease-out', style({
        transform: 'translate(45%, 0)', opacity: 0
      }))
    ])
  ])

];
