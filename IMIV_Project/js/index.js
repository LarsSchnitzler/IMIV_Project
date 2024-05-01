//-------------------Functions-------------------
function displayDataset(unit, dta, datasetName, sr, container){ //canvasContainer should be id of container, data should be object with date_time as keys, titleString should be string
  //---------create box----------
  const ct = document.createElement('div');
  ct.className = 'canvas-container';

  const title = document.createElement('h3');
  title.textContent = datasetName + '[' + unit + '] over time';
  ct.appendChild(title);

  const canvas = document.createElement('canvas');
  ct.appendChild(canvas);

  const ctx = canvas.getContext('2d');

  //append box to container
  container.appendChild(ct);
  
  //----------create chart----------

  //get labels and data from argument 'dta'
  const originalLabels = Object.keys(dta);
  const labels = originalLabels.map((label, index) => index % 4 === 0 ? label : '');

  const data = Object.values(dta);

  //create an object for the annotations (sunset from array 'sr')
  let annots = {};

  //add a line for each sunrise time
  for (let i = 0; i < sr.length; i++) {
    annots['line' + (i + 1)] = {
      type: 'line',
      mode: 'vertical',
      value: sr[i],
      borderColor: 'rgb(255, 255, 0)', 
      borderWidth: 4
    };
  }

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          data: data,
          borderColor: 'rgb(25, 50, 100)',
          borderWidth: 2,
          tension: 0.25,
          pointRadius: 0
        }
      ]
    },
    options: {
      plugins: {
        legend: { display: false },
        annotation: { annotations: annots}
      },
      scales: {
        x: { ticks: { autoSkip: false, maxRotation: 90, minRotation: 90  } },
        y: { beginAtZero: true, title: { display: true, text: unit } }
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

  //seperate dataset 'sunrise' from rest of data
  const sunrise = data['data']['sunrise'];
  delete data['data']['sunrise'];
  //concatenate sunrise datetime values
  const sunriseDatetimes = [];
  for (let [date, time] of Object.entries(sunrise)) {
    let datetimeString = date + ' ' + time.substring(0,2) + ':00:00';
    sunriseDatetimes.push(datetimeString);
  }

  //get array of datasetNames
  const keys = Object.keys(data['data']);

  for (let i = 0; i < keys.length; i++) {
    //get unit for datasetName
    ut = data['units'][keys[i]];

    //get dataset for datasetName
    ds = data['data'][keys[i]];

    //get datasetName
    dsName = keys[i];

    displayDataset(ut, ds, dsName, sunriseDatetimes, chartContainer);
  }

  return true;
}

//-------------------Main Code/Eventlisteners-------------------
document.addEventListener('DOMContentLoaded', () => {
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