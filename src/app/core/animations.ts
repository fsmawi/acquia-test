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
  ]),
  trigger('zoomIn', [
    state('void', style({
      transform: 'scale(0)'
    })),
    state('initial', style({
      transform: 'scale(0)'
    })),
    state('enter', style({
      transform: 'scale(1)'
    })),
    state('leave', style({
      transform: 'scale(0)'
    })),
    transition(':enter', [
      animate('150ms cubic-bezier(0.0, 0.0, 0.2, 1)')
    ]),
    transition(':leave', [
      animate('150ms cubic-bezier(0.4, 0.0, 1, 1)')
    ]),
  ])

];
