import 'core-js';
import * as React from 'react';
import ReactDom from 'react-dom';
import { AuthenticationContainer } from './components/AuthenticationContainer';
import { translate } from './translations';

ReactDom.render(<AuthenticationContainer t={translate} />, document.getElementById('root'));
