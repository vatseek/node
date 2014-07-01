var MongoClient = require('mongodb').MongoClient
var async = require('async');

MongoClient.connect('mongodb://127.0.0.1:27017/road_dev', function(err, db) {
    if (err) throw err;
    console.log('connected to db');


    var beetwen = function(x1,y1,x2,y2,xd,yd){
        var res = 0;
        if ((x1 > xd && xd > x2) || (x1 < xd && xd < x2)) {
            res++;
        }

        if ((y1 > yd && yd > y2) || (y1 < yd && yd < y2)) {
            res++;
        }

        return res;
    }

    var updateSector = function(sector, route_id, callback) {

        var s = db.collection('sector'). ({_id: sector._id}, function(err, dbSector) {
            console.log(dbSector);
        });

        console.log(s);

//        db.collection('sector').find({_id: sector._id}, function(err, dbSector) {
//            dbSector.next(function(call){
//                console.log(call);
//            });
//
//            dbSector = dbSector.next();
//
//            if (dbSector) {
//                var pitCount = 1;
//                if (dbSector.pit_cnt) {
//                    pitCount = dbSector.pit_cnt + 1;
//                }
//
//                var routes = [];
//                if (dbSector.route) {
//                    routes = dbSector.route;
//                }
//
//                route_id = route_id + '';
//                if (!~routes.indexOf(route_id)) {
//                    routes.push(route_id);
//                }
//
//                db.collection('sector').update({_id: sector._id}, {$set: {
//                    pit_cnt: pitCount,
//                    route: routes
//                }}, callback);
//            } else {
//                callback();
//            }
//        });
    }

    var calls = [];
    db.collection('routes').find({gone: {$exists: false}}).each(function(err, route){
        if (err) throw err;
        if (route === null) {
            return;
        }

        calls.push(function(callback) {
            var marksCount = 0;
            db.collection('marks').find({r: route._id, p: {$gt: route['pit_avg'] * 1.5}}).each(function(err, mark) {
                if (err) throw callback(err);
                if (mark === null) {
                    return;
                }
                marksCount++;
                db.collection('sector').findOne({start:{ $near : [mark.g.x, mark.g.y] }}, function(err, startSector) {
                    if (err) throw err;
                    db.collection('sector').findOne({end:{ $near : [mark.g.x, mark.g.y] }}, function(err, endSector) {
                        if (err) throw err;

                        if (startSector._id == endSector._id) {
                            updateSector(startSector, route._id, function(err, sector) {});
                        } else {
                            var res1 = beetwen(startSector.start.x, startSector.start.y, startSector.end.x, startSector.end.y, mark.g.x, mark.g.y)
                            var res2 = beetwen(endSector.start.x, endSector.start.y, endSector.end.x, endSector.end.y, mark.g.x, mark.g.y)
                            updateSector(res1 >= res2 ? startSector: endSector, route._id, function(err, data) {});
                        }
                    });
                });

                callback(null, mark);
            });

        });

//        console.log(calls.length);

        var q = async.series(calls, function(err, result) {
            if (err)
                return console.log(err);
            //console.log(result);
        });
    });
});
