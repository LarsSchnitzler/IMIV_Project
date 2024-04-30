//-------------------Functions-------------------
function displayDataset(unit, dta, datasetName, container){ //canvasContainer should be id of container, data should be object with date_time as keys, titleString should be string
  //---------create box----------
  const ct = document.createElement('div');
  ct.className = 'canvas-container';

  const title = document.createElement('h3');
  title.textContent = datasetName + ' over time';

  ct.appendChild(title);

  const canvas = document.createElement('canvas');

  ct.appendChild(canvas);

  const ctx = canvas.getContext('2d');

  //append box to container
  container.appendChild(ct);
  //----------create chart----------

  //get labels and data from argument 'dta'
  const labels = Object.keys(dta);
  const data = Object.values(dta);

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        borderColor: 'rgb(25, 50, 100)',
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

}

//-------------------Async Functions-------------------
async function fetchData(d,dback) {
  //check if d and dback is a valid date of format yyyy-mm-dd
  console.log('fetchData called with d:', d, 'dback:', dback);
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  if (!dateRegex.test(d) || isNaN(dback)) {
    console.log('fetchData recieved invalid date format (meaning not yyyy-mm-dd) or dback is not a number');
    return false;
  }

  try {
    const params = new URLSearchParams();
    params.append('day', d);
    params.append('daysBack', dback);

    const response = await fetch('./db_to_frontend.php?' + params.toString());
    const data = await response.json();
    console.log('Data fetched:', data);
    return data;
  } catch (error) {
    console.log('Error:', error);
    return null;
  }
}

async function main(timespan) {
  let data;
  const chartContainer = document.getElementById('chart-container');
  chartContainer.innerHTML = '';

  if (timespan === 'today'){
    data = await fetchData(new Date().toISOString().slice(0,10), 0);
  }
  else if (timespan === '1week'){
    data = await fetchData(new Date().toISOString().slice(0,10), 6);
  }
  else if (timespan === '2week'){
    data = await fetchData(new Date().toISOString().slice(0,10), 13);
  }
  else {
    console.log('main recieved invalid timespan');
    return false;
  }

  //get array of datasetNames
  const keys = Object.keys(data['data']);

  for (let i = 0; i < keys.length; i++) {
    //get unit for datasetName
    console.log('Data in main():', data['units']);
    console.log(keys[i]);
    ut = data['units'][keys[i]];
    console.log('ut:', ut);

    //get dataset for datasetName
    ds = data['data'][keys[i]];

    //get datasetName
    dsName = keys[i];

    displayDataset(ut, ds, dsName, chartContainer);
  }

  return true;
}

//-------------------Main Code/Eventlisteners-------------------
document.addEventListener('DOMContentLoaded', (event) => {
  console.log('DOM fully loaded and parsed');
  btnToday = document.getElementById('btnToday');
  btn1Week = document.getElementById('btn1w');
  btn2Week = document.getElementById('btn2w');

  main('today');

  btnToday.addEventListener('click', () => {
    main('today');
  });
  btn1Week.addEventListener('click', () => {
    main('1week');
  });
  btn2Week.addEventListener('click', () => {
    main('2week');
  });
});